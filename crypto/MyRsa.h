#ifndef HEADER_MYRSA_H
#define HEADER_MYRSA_H
#include "stdafx.h"
#include "stdio.h"
#include <openssl/rand.h>
#include <openssl/rsa.h>
#include <openssl/engine.h>

class MyRsa{
public:
	MyRsa();
	~MyRsa();
	//void generatePkeys();
	//int publicEncrypt(unsigned char* data, unsigned char* dataEncrypted,int dataLen);
	//int privateDecrypt(unsigned char* dataEncrypted, unsigned char* dataDecrypted)
	void SendPublic();
	int file_to_encrypt();
private:
protected:
};
#endif
