<?php
    error_reporting(E_ALL); 
    
    $host = "";
    $port = 9000;
    
    $server = "";
    $username = "";
    $password = "";
    $database = "network";
    
    $peers = array();
    $socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
    socket_bind($socket,'127.0.0.1',$port);
    socket_listen($socket);
    socket_set_nonblock($socket);
    
    while(true){
        if(($newc = socket_accept($socket)) !== false){
            echo "Client $newc has connected\n";
            $peers[] = $newc;
            handle_peer($newc);
        }
    }
    
    /**
     * For when a peer wants to upload a file.
     * If that file doesn't exist create a record of it in the torrents table.
     * In any case add the peer as a seed for the file.
     */
    function upload_torrent($socket, $name, $size){
        socket_getpeername($socket, $ip);
        $peerquery = sprintf("SELECT peerid FROM peers WHERE ip=%s", $ip);
        $peerid = mysql_query($peerquery, $con);
        $query = sprintf("SELECT name FROM torrents WHERE name = %s;", $name);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) == 0){
            $query = sprintf("INSERT INTO torrents (torrent_name, size_MB) VALUES ('%s', '%d');", $name, $size);
            if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
        }
        $query = sprintf("INSERT INTO seeds (peerid, torrent_name) VALUES ('%d', '%s');", $peerid, $name);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
    }
    
    /**
     * For when a peer wants to download a particular file.
     * If the file doesn't exist send an error.
     * Otherwise insert a new record of the peer as a leecher and send a request for the first chunk.
     */
    function download_torrent($socket, $name){
        socket_getpeername($socket, $ip);
        $query = sprintf("SELECT torrent_name FROM torrents WHERE torrent_name=%s;", $name);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) > 0){
            $peerquery = sprintf("SELECT peerid FROM peers WHERE ip=%s;", $ip);
            $peerid = mysql_query($peerquery, $con);
            $query = sprintf("INSERT INTO leecher (peerid, torrent_name, chunk_progress) VALUES (%d, %s, %d);", $peerid, $name, 0);
            if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
            send_chunk($socket, $name, 0);
        }else{send_error($socket, "File does not exist");} 
    }
    
    /**
     * When a chunk has not been successfully received by the leecher.
     * Just send it again.
     */
    function chunk_failed($socket, $name, $chunk_num){
        send_chunk($socket, $name, $chunk_num);
    }
    
    /**
     * When a chunk has been successfully received by the leecher.
     * Update the leecher entry with a new chunk number and then send the next chunk.
     */
    function chunk_confirmed($socket, $chunk_num){
        socket_getpeername($socket, $ip);
        $peerquery = sprintf("SELECT peerid FROM peers WHERE ip=%s", $ip);
        $peerid = mysql_query($peerquery, $con);
        $query = sprintf("UPDATE leechers SET chunk_progress = '%d' WHERE peerid = '%d';", ($chunk_num+1), $peerid);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
        send_chunk($socket, $name, $chunk_num+1);
    }
    
    /**
     * When a leecher has completed their download.
     * Add a new record into the seeds table for the peer and delete their previous leecher entry.
     */
    function download_complete($socket, $name){
        socket_getpeername($socket, $ip);
        $peerquery = sprintf("SELECT peerid FROM peers WHERE ip=%s", $ip);
        $peerid = mysql_query($peerquery, $con);
        $query = sprintf("INSERT INTO seeds (peerid, torrent_name) VALUES ('%d', '%s');
                          DELETE FROM leechers WHERE peerid = %d;", $peerid, $name, $peerid);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
    }
    
    /**
     * Tell a seed to send a particular chunk to a peer.
     */
    function send_chunk($leechsocket, $name, $chunk_num){
        $firstseed = sprintf("SELECT TOP 1 peerid FROM seeds WHERE torrent_name = %s;", $name);
        $seedid = mysql_query($firstseed, $con);
        $seed = sprintf("SELECT ip FROM peers WHERE peerid = %d", $seedid);
        $seedip = mysql_query($seed, $con);
        // I think we need to add a port to the database
        $seedport = 2323;
        
        $buf = makeGetFrame($name, $chunk_num);
        
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_sendto($socket, $buf, strlen($buf), 0, $seedip, $seedport);
        socket_close($socket);
    }
    
    /**
     * Send a error message back to a peer.
     */
    function send_error($socket, $error_message){
        echo "haven't done this yet";
    }
    
    /**
     * Handle a connection made by a single peer.
     */
    function handle_peer($socket){
        $peer = socket_getpeername($socket, $ip);
        
        $con = mysql_connect($database, $username, $password);
        if (!$con){
            die('Could not connect: ' . mysql_error());
        }
        mysql_select_db($database, $con);
        
        if(peerNotOnRecord($ip)){
            addPeer($ip);
        }
        
        $buf = 'This is my buffer.';
        $bytes = socket_recv($socket, $buf, 2048, MSG_WAITALL);
        socket_close($socket);
        
        echo $buf;
        
        $conn_type = getType($buf);
        
        switch ($conn_type) {
            case 0:
                $name = getName($buf);
                $size = getSize($buf);
                upload_torrent($socket, $name, $size);
                break;
            case 1:
                $name = getName($buf);
                download_torrent($socket, $name);
                break;
            case 2:
                $chunk_num = getChunkNum($buf);
                chunk_failed($socket, $chunk_num);
                break;
            case 3:
                $chunk_num = getChunkNum($buf);
                chunk_confirmed($socket, $chunk_num);
                break;
            case 4:
                $name = getName($buf);
                download_complete($socket, $name);
                break;
        }
    }
    
    /**
     * Check if this is the first time the peer has connected.
     */
    function peerNotOnRecord($ip){
        $query = sprintf("SELECT * FROM peers WHERE ip = '%s';", $ip);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) > 0){
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * Add a record of the peer to the database.
     */
    function addPeer($ip){
        $query = sprintf("INSERT INTO peers (ip) VALUES (%s);", $ip);
    }
    
     /**
     * Sends 18x8 MCUF-UDP packet to target host.
     *
     * see also:
     * wiki.blinkenarea.org/index.php/MicroControllerUnitFrame
     *
     * @param array     $frame 18x8 '0' or '1'
     * @param int       $delay delay in msec
     * @param string    $host target host
     * @param int       $port target port (udp)
     */
    function send_frame($frame, $delay, $host="192.168.0.23", $port=2323) {
        $header = "\x23\x54\x26\x66\x00\x08\x00\x12\x00\x01\x00\xff";
        $buf = $header;
        for ($i=0;$i<8;$i++) {
            for ($j=0;$j<18;$j++) {
                if ($frame[$i][$j]) {
                    $buf.="\xff";
                } else  {
                    $buf.="\x00";
                }
            }
        }
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_sendto($socket, $buf, strlen($buf), 0, $host, $port);
        socket_close($socket);
        usleep($delay*1000);
    }
?>