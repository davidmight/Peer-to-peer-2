<?php
	$server = "";
    $username = "";
    $password = "";
    $database = "";
	
     $con = mysql_connect($server, $username, $password);
    if (!$con){
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db($database, $con);
    
    $peer_id = $_GET["peer_id"];
    $ip = $_SERVER["REMOTE_ADDR"];
    $port = $_GET["port"];
    $public_key = $_GET["public_key"];
    
    if(peerNotOnRecord($peer_id, $ip, $port, $public_key)){
        addPeer($peer_id, $ip, $port, $public_key);
    }

	$otherId = $_GET["other_id"];
	returnKey($otherId);
	
	mysql_close($con);
	
	function returnKey($otherId){
		$query = sprintf("SELECT public_key FROM peers WHERE peer_id = %d", 
            $otherId
        );
		$result = mysql_query($query, $con);
		echo $result;
	}

	/**
     * Check if this is the first time the peer has connected.
     */
    function peerNotOnRecord($peer_id, $ip, $port, $public_key){
        $query = sprintf("SELECT * FROM peers WHERE peerid = '%s';", $peer_id);
        $check = mysql_query($query, $con);
        if(mysql_num_rows($check) > 0){
            $row = mysql_fetch_assoc($check);
            if($row["port"] != $port || $row["ip"] != $ip || $row["public_key"] != $public_keys){
                $query = sprintf("UPDATE peers SET ip = '%s', port = '%d', public_key = '%s' WHERE peerid = '%s';", $ip, $port, $public_key, $peer_id);
                if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
            }
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * Add a record of the peer to the database.
     */
    function addPeer($peer_id, $ip, $port, $public_key){
        $query = sprintf("INSERT INTO peers (peerid, ip, port, public_key) VALUES ('%s', '%s', %d, '%s');", $peer_id, $ip, $port, $public_key);
        if(!mysql_query($query, $con)){echo('Error: ' . mysql_error());}
    }
?>
