<?php
    error_reporting(E_ALL); 
    
    $username = "";
    $password = "";
    $database = "network";
    
    $con = mysql_connect($database, $username, $password);
    if (!$con){
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db($database, $con);
    
    $peer_id = $_GET["peer_id"];
    $ip = $_GET["ip"];
    $port = $_GET["port"];
    
    if(peerNotOnRecord($peer_id, $ip, $port)){
        addPeer($peer_id, $ip, $port);
    }
    
    $conn_type = $_GET["type"];
    
    switch ($conn_type) {
        case 0:
            $name = $_GET["name"];
            $size = $_GET["size"];
            upload_torrent($peer_id, $name, $size);
            break;
        case 1:
            $name = $_GET["name"];
            download_torrent($peer_id, $name);
            break;
        case 2:
            $name = $_GET["name"];
            chunk_failed($name);
            break;
        case 3:
            $name = $_GET["name"];
            $chunk_num = $_GET["left"];
            chunk_confirmed($peer_id, $name, $chunk_num);
            break;
        case 4:
            $name = $_GET["name"];
            download_complete($peer_id, $name);
            break;
    }
    
    /**
     * For when a peer wants to upload a file.
     * If that file doesn't exist create a record of it in the torrents table.
     * In any case add the peer as a seed for the file.
     */
    function upload_torrent($peer_id, $name, $size){
        $query = sprintf("SELECT name FROM torrents WHERE name = %s;", $name);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) == 0){
            $query = sprintf("INSERT INTO torrents (torrent_name, size_MB) VALUES ('%s', '%d');", $name, $size);
            if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
        }
        $query = sprintf("INSERT INTO seeds (peerid, torrent_name) VALUES ('%d', '%s');", $peer_id, $name);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
    }
    
    /**
     * For when a peer wants to download a particular file.
     * If the file doesn't exist send an error.
     * Otherwise insert a new record of the peer as a leecher and return a list of seeds.
     */
    function download_torrent($peer_id, $name){
        $query = sprintf("SELECT torrent_name FROM torrents WHERE torrent_name=%s;", $name);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) > 0){
            $query = sprintf("INSERT INTO leecher (peerid, torrent_name, chunk_progress) VALUES (%d, %s, %d);", $peer_id, $name, 0);
            if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
            return_list($name);
        }else{echo json_encode("File does not exist");} 
    }
    
    /**
     * When a chunk has not been successfully received by the leecher.
     * Just return a list of seeds.
     */
    function chunk_failed($name){
        return_list($name);
    }
    
    /**
     * When a chunk has been successfully received by the leecher.
     * Update the leecher entry with a new chunk number and return a list seeds.
     */
    function chunk_confirmed($peer_id, $name, $chunk_num){
        $query = sprintf("UPDATE leechers SET chunk_progress = '%d' WHERE peerid = %s;", $chunk_num, $peer_id);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
        return_list($name);
    }
    
    /**
     * When a leecher has completed their download.
     * Add a new record into the seeds table for the peer and delete their previous leecher entry.
     */
    function download_complete($peer_id, $name){
        $query = sprintf("INSERT INTO seeds (peerid, torrent_name) VALUES ('%s', '%s');
                          DELETE FROM leechers WHERE peerid = '%s';", $peer_id, $name, $peer_id);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
    }
    
    /**
     * Return a list of seeds.
     * Returns a maximum of 50 seeds. 
     */
    function return_list($name){
        $list = array();
        //$query = sprintf("SELECT peerid, ip, port FROM peers WHERE 
        //                  peerid = (SELECT peerid FROM seeds WHERE torrent_name = %s);", $name);
        $query = sprintf("SELECT p.peerid as peerid, p.port as port, s.torrent_name as torrent_name FROM peers as p INNER JOIN seeds as s ON s.peerid=p.peerid WHERE s.torrent_name='%s' LIMIT 50;", 
            mysql_escape_string($name)
        );
        $result = mysql_query($query, $con);
        if(mysql_num_rows($result) > 0){
            while($row = mysql_fetch_assoc($result)){
                $seed->peer_id = $row["peerid"];
                $seed->ip = $row["ip"];
                $seed->port = $row["port"];
                $list .= $seed;
            }
        }else{echo json_encode("There are no seeds.");}
        echo json_encode($list);
    }
    
    /**
     * Check if this is the first time the peer has connected.
     */
    function peerNotOnRecord($peer_id, $ip, $port){
        $query = sprintf("SELECT * FROM peers WHERE peerid = '%s';", $peer_id);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) > 0){
            $row = mysql_fetch_assoc($check);
            if($row["port"] != $port || $row["ip"] != $ip){
                $query = sprintf("UPDATE peers SET ip = '%s', port = '%d' WHERE id = '%s';", $row["ip"], $row["port"], $peer_id);
                if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
            }
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * Add a record of the peer to the database.
     */
    function addPeer($peer_id, $ip, $port){
        $query = sprintf("INSERT INTO peers (ip) VALUES (%s);", $ip);
    }
   
?>