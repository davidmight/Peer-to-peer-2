/*
 *  peerDict.cpp
 *  peerParser
 *
 *  Created by David Byrne on 22/03/2012.
 *  Copyright 2012 Trinity College. All rights reserved.
 *
 */

#include "PeerDict.h"

PeerDict :: PeerDict(mObject obj){
	peer_id = obj["peer_id"].get_str();
	ip = obj["ip"].get_str();
	port = atoi(obj["port"].get_str().c_str());
	public_key = obj["public_key"].get_str();
}

string PeerDict::getId(){
	return this->peer_id;
}

string PeerDict::getIp(){
	return this->ip;
}

int PeerDict::getPort(){
	return this->port;
}

string PeerDict::getPublicKey(){
	return this->public_key;
}