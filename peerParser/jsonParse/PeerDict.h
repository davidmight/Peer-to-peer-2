/*
 *  peerParser.h
 *  
 *
 *  Created by David Byrne on 22/03/2012.
 *  Copyright 2012 Trinity College. All rights reserved.
 *
 */

#include <stdio.h>
#include <stdlib.h>
#include <string>
#include "json_spirit.h"

using namespace std;
using namespace json_spirit;

class PeerDict {
private:
	string peer_id;
	string ip;
	int port;
	string public_key;
public:
	PeerDict(mObject obj);
	string getId();
	string getIp();
	int getPort();
	string getPublicKey();
};

