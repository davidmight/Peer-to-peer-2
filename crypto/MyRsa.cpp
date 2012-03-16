#include "stdafx.h"
#include "MyRsa.h"
#include "stdio.h"
#include <openssl/rand.h>
#include <openssl/pem.h>
#include <openssl/rsa.h>
#include <openssl/engine.h>
#include <openssl/x509.h>
#include <openssl/aes.h>
#include <openssl/des.h>
#include <openssl/applink.c>
using namespace std;
//libeay32mt.lib

MyRsa::MyRsa(){

}

MyRsa::~MyRsa(){

}

int main(){
	int i;
	printf("\t\t Ladies And Gentlemen Roll the Dice \n\n");
	size_t result;												//Used in fread  should always be == flength 
	int z=0;
	int checkE=0, checkD=0, checkPrivE =0,checkPubD=0, check=0;	//Check values used to determine if encryption/decryption was sucessful 
	int flength =0;
	long maybe=0;												//intialize flength to 0;
	unsigned char *stuff = NULL,*to_e = NULL, *to_d = NULL;		//Give values  !!!! Stuff contains file.txt in a buffer sorta thing 
	stuff = (unsigned char*)malloc(4*sizeof(unsigned char));	//Might need to malloc to flength?!!!
/*************************************************************************************************************************************/
	unsigned char ckey[] =  "thiskeyisverybad";
	unsigned char Desout[] =  "Hello wolrd im a";
	
	//AES
	// Generate AES key etc. 
	// Store into RSALAB11.txt or (whatever)
	// Take in the key as what is to be encrypted by RSA.	
	//AES_KEY * key;
	//AES_encrypt(ckey,cout,key);
	//FILE *Aes;
	//Aes = fopen("Aes.txt","w");	
	
	
	//DES_cblock DesKey;
	//DES_key_schedule schedule;
	//DES_set_odd_parity( &DesKey );
   // DES_set_key_checked( &DesKey, &schedule );
   // DES_cfb64_encrypt( ckey,Desout,sizeof(ckey), &schedule, &DesKey, 0, DES_ENCRYPT );

/*************************************************************************************************************************************/
	printf("Reading In Message To Be Encrypted\n");
	FILE *file;								//Create File file
		file = fopen("Rsalab.txt","rb");		//Open file		
		fseek(file,0,SEEK_END);					//run to end of file.	
		flength = ftell(file);					//ftell gets length of file @ point which is end since fseek
		rewind (file);							//rewind file	
		stuff = (unsigned char*)malloc(sizeof(unsigned char)*flength);	//
		result = fread(stuff,1,long(flength),file);	//fread...stuff should now have contents of txt file...
			if(result < 0 || result!=flength || result == NULL){
				printf("Fatal Error occured During fread\n");
				printf ("Size of file : %ld bytes.\n",flength);
				printf ("Size of file : %ld bytes.\n",result);				
				scanf ("%d",&i);
				return -1;
			}
		fclose (file);							// close file		
		printf("Message Has Been Read\n");
		printf ("Size of file : %ld characters.\n",flength);	//print statement debug prints ....
/*************************************************************************************************************************************/		

	printf("Generating RSA Key Pair....\n");						//print statement debug etc....
	RSA* rsa = RSA_generate_key(1024, 65537, NULL, NULL);		//Generate Rsa key stored in rsas(Uses openssl libary etc)
		if(RSA_check_key(rsa) < 1 ){								//returns 1 if valid, and 0 otherwise. -1 if an error occurs while checking.
			printf("Fatal Error occured in Generation\n");			// Check to see if the generated key is Ligit 
			scanf ("%d",&i);
			return -1;
		}											
	printf("Key Generation Complete\n");					//print statement debug etc....	
/*************************************************************************************************************************************/
	printf("Spliting Key Pair & File Creation\n");
	RSA* rsa_pubkey;
	RSA* rsa_privatekey;
	rsa_privatekey = RSAPrivateKey_dup(rsa);
	rsa_pubkey = RSAPublicKey_dup(rsa);	

	FILE *file2, *file3, *file4;
	file2 = fopen("RsaPubKey.txt","w");
	file3 = fopen("RsaPrivKey.txt","w");
	file4 = fopen("FULLRsaKey.txt","w");

	RSA_print_fp(file2,rsa_pubkey,2);
	RSA_print_fp(file3,rsa_privatekey,2);
	RSA_print_fp(file4,rsa,2);

	fclose(file2);
	fclose(file3);
	fclose(file4);
	printf("Spliting Key Pair & File Creation Complete\nPress anykey to continue\n");
	scanf ("%d",&i);
/*************Ensure correct length of flength for Padding etc************************************************************************/

	to_e = (unsigned char*)malloc(RSA_size(rsa));			//Must be of size  RSA_size(rsa) bytes of memory..
	to_d = (unsigned char*)malloc(RSA_size(rsa));			//Must be  "  "       "            "    "   "
			
		if(!(flength<RSA_size(rsa)-11)){
			printf("Fatal Error occured During flength to long\n");
			printf("Flength : %ld < %d \n",flength,(RSA_size(rsa)-11));
			scanf("%d",&i);
			return -1;
		}
/*************************************************************************************************************************************/
	printf("Encrypting Message\n");
	checkE = RSA_public_encrypt(flength,stuff,to_e,rsa_pubkey, RSA_PKCS1_PADDING);	//Encrypt data from(blah) to (blah) using (rsa), padding and flength given
				//printf ("to_e : %s \n",to_e);
				//printf ("stuff : %s \n",stuff);
				//printf ("to_d : %s \n",to_d);
		if(checkE < 0){
			printf("Fatal Error occured in encryption\n");
			scanf ("%d",&i);
			while(z!=1){
				scanf ("%d",&i);
			}
			return -1;		
		}
	printf("Message has been encrypted CheckE = %d \n",checkE);					//print statement debug etc....
	scanf ("%d",&i);
/*************************************************************************************************************************************/
	printf("Decrypting Message\n");
	checkD = RSA_private_decrypt(RSA_size(rsa_privatekey),to_e,to_d,rsa_privatekey, RSA_PKCS1_PADDING);		//Dncrypt data from(blah) to (blah) using (rsa), padding and flength given
				//printf ("to_e : %s \n",to_e);
				//printf ("stuff : %s \n",stuff);
				//printf ("to_d : %s \n",to_d);	
		if(checkD < 0){
			printf("Fatal Error occured in decryption CheckD = %d \n",checkD);
			
			while(z!=1){
				scanf ("%d",&i);
			}
			scanf ("%d",&i);
			return -1;
		}
	printf("Message has been decrypted\n");					//print statement debug etc....
/*************************************************************************************************************************************/
	printf("Returning Decrypted Message To File\n");
	FILE * rfile;
	rfile = fopen ( "rfile.txt" , "wb" );
	fwrite (to_e,1, flength, rfile);
	fclose (rfile);
	
	
	printf("Returning Decrypted Message To File\n");
	FILE * resultfile;
	resultfile = fopen ( "resultfile.txt" , "wb" );
	fwrite (to_d,1, flength, resultfile);
	fclose (resultfile);
	if(memcmp(stuff,to_d,flength)!=0){										//not sure if should be 256 ir flength?!!!
		printf("Fatal Error occured Decrypted Data doesnt match orignal\n");
		
		scanf ("%d",&i);
		return -1;
	}else{
	printf("Message Is all Good\n");					//print statement debug etc....
	
	}
	printf("Message Has Been Saved To File\n");
/*************************************************************************************************************************************/	
	while(z!=1){
				scanf ("%d",&i);
			}
	scanf ("%d",&i);
	return 0;

}