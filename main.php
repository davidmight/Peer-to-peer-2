<?php
    error_reporting(E_ALL); 
    
    $host = "";
    $port = 0000;
    
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
        }
    }
    
    $con = mysql_connect($database, $username, $password);
    if (!$con){
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db($database, $con);
    
    switch ($conn_type) {
        case 0:
            upload_torrent();
            break;
        case 1:
            download_torrent();
            break;
        case 2:
            chunk_failed();
            break;
        case 3:
            chunk_confirmed();
            break;
        case 4:
            download_complete();
            break;
    }
    
    /**
     * For when a peer wants to upload a file.
     * If that file doesn't exist create a record of it in the torrents table.
     * In any case add the peer as a seed for the file.
     */
    function upload_torrent($ip, $name, $size){
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
    function download_torrent($ip, $name){
        $query = sprintf("SELECT torrent_name FROM torrents WHERE torrent_name=%s;", $name);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) > 0){
            $peerquery = sprintf("SELECT peerid FROM peers WHERE ip=%s;", $ip);
            $peerid = mysql_query($peerquery, $con);
            $query = sprintf("INSERT INTO leecher (peerid, torrent_name, chunk_progress) VALUES (%d, %s, %d);", $peerid, $name, 0);
            if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
            send_chunk($ip, 0);
        }else{send_error($ip, "File does not exist");} 
    }
    
    function chunk_failed($ip, $chunk_num){
        send_chunk($ip, $chunk_num);
    }
    
    /**
     * When a chunk has been successfully been received by the leecher.
     * Update the leecher entry with a new chunk number and then send the next chunk.
     */
    function chunk_confirmed($ip, $chunk_num){
        $peerquery = sprintf("SELECT peerid FROM peers WHERE ip=%s", $ip);
        $peerid = mysql_query($peerquery, $con);
        $query = sprintf("UPDATE leechers SET chunk_progress = '%d' WHERE peerid = '%d';", ($chunk_num+1), $peerid);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
        send_chunk($ip, $chunk_num+1);
    }
    
    /**
     * When a leecher has completed their download.
     * Add a new record into the seeds table for the peer and delete their previous leecher entry.
     */
    function download_complete($ip, $name){
        $peerquery = sprintf("SELECT peerid FROM peers WHERE ip=%s", $ip);
        $peerid = mysql_query($peerquery, $con);
        $query = sprintf("INSERT INTO seeds (peerid, torrent_name) VALUES ('%d', '%s');
                          DELETE FROM leechers WHERE peerid = %d;", $peerid, $name, $peerid);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
    }
    
    function recv_frame($host, $port) {
        error_reporting(E_ALL);
        
        /* Create a TCP/IP socket. */
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        } else {
            echo "Socket created.\n";
        }
        
        echo "Attempting to connect to '$host' on port '$port'...";
        $result = socket_connect($socket, $host, $port);
        if ($result === false) {
            echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
        } else {
            echo "Socket connected.\n";
        }
        
        echo "Reading response:\n\n";
        $buf = 'This is my buffer.';
        if (false !== ($bytes = socket_recv($socket, $buf, 2048, MSG_WAITALL))) {
            echo "Read $bytes bytes from socket_recv(). Closing socket...";
        } else {
            echo "socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n";
        }
        socket_close($socket);
        
        echo $buf . "\n";
        return $buf;
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
    
    function send_chunk($ip, $chunk_num){
        
    }
    
    function send_error($ip, $error_message){
        
    }
?>