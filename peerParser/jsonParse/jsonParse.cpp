// peerParse.cpp : Defines the entry point for the console application.
//

#include <boost/asio.hpp>
#include <string>
#include "json_spirit.h"
#include "PeerDict.h"

using boost::asio::ip::tcp;
using namespace json_spirit;
using namespace std;

boost::asio::streambuf * sendMessage(string data){
	try{
		string host = "localhost";
		boost::asio::io_service io_service;

		// Get a list of endpoints corresponding to the server name.
		tcp::resolver resolver(io_service);
		tcp::resolver::query query(host, "http");
		tcp::resolver::iterator endpoint_iterator = resolver.resolve(query);

		// Try each endpoint until we successfully establish a connection.
		tcp::socket socket(io_service);
		boost::asio::connect(socket, endpoint_iterator);

		// Form the request. We specify the "Connection: close" header so that the
		// server will close the socket after transmitting the response. This will
		// allow us to treat all data up until the EOF as the content.
		boost::asio::streambuf request;
		std::ostream request_stream(&request);
		request_stream << "GET " << data << " HTTP/1.1\r\n";
		request_stream << "Host: " << host << "\r\n";
    	request_stream << "Accept: */*\r\n";
		request_stream << "Connection: close\r\n\r\n";
	    
		// couldn't get post to work not using get
		//request_stream << "Content-Length: 13 \r\n Content-Type: application/x-www-form-urlencoded \r\n";

		//std::cout << "is this being called?\n";
    	// Send the request.
    	boost::asio::write(socket, request);

		// Read the response status line. The response streambuf will automatically
    	// grow to accommodate the entire line. The growth may be limited by passing
    	// a maximum size to the streambuf constructor.
    	boost::asio::streambuf *response = new boost::asio::streambuf();
    	boost::asio::read_until(socket, *response, "\r\n");

    	// Check that response is OK.
    	std::istream response_stream(response);
    	std::string http_version;
    	response_stream >> http_version;
    	unsigned int status_code;
    	response_stream >> status_code;
    	std::string status_message;
    	std::getline(response_stream, status_message);
    	if (!response_stream || http_version.substr(0, 5) != "HTTP/")
    	{
      		std::cout << "Invalid response\n";
    		//return ;
    	}
		 /*if (status_code != 200)
    		{
    			std::cout << "Response returned with status code " << status_code << "\n";
    				  return ;
    		}
		*/
   		// Read the response headers, which are terminated by a blank line.
   		boost::asio::read_until(socket, *response, "\r\n\r\n");

    	// remove the response headers.
    	std::string header;
    	while (std::getline(response_stream, header) && header != "\r");

		return  response;
	}catch(std::exception& e){
		cout << "Exception: " << e.what() << "\n";
	}
}

vector<PeerDict> read_peers(string jsonString){
	mValue value;
	read(jsonString, value);
	mArray listOfPeers = value.get_array();
	vector<PeerDict> peers;

	for(vector<PeerDict>::size_type i=0; i<listOfPeers.size(); i++){
		PeerDict peer(listOfPeers[i].get_obj());
		peers.push_back(peer);
	}

	return peers;
}

int main(void)
{
	//cout << sendMessage("/test.php");
	boost::asio::streambuf * sb = sendMessage("/test.php");
	string s((istreambuf_iterator<char>(sb)), 
               istreambuf_iterator<char>());
	vector<PeerDict> peers = read_peers(s);
	
	for(int i=0; i<peers.size(); i++){
		cout << "Peer-id: " << peers.at(i).getId() << "\n"
			 << "Ip: " << peers.at(i).getIp() << "\n"
			 << "Port: " << peers.at(i).getPort() << "\n"
			 << "Public-key: " << peers.at(i).getPublicKey() << "\n\n";
	}

	return 0;
}