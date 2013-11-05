"""                                                                                                                                                                   
Example Python EMDR client.                                                                                                                                           
"""                                                                                                                                                                   
import zlib                                                                                                                                                           
import zmq                                                                                                                                                            
# You can substitute the stdlib's json module, if that suits your fancy                                                                                               
import simplejson                                                                                                                                                     
import time                                                                                                                                                           
                                                                                                                                                                      
def main():                                                                                                                                                           
    context = zmq.Context()                                                                                                                                           
    subscriber = context.socket(zmq.SUB)                                                                                                                              
                                                                                                                                                                      
    # Connect to the first publicly available relay.                                                                                                                  
    subscriber.connect('tcp://relay-us-central-1.eve-emdr.com:8050')                                                                                                  
    # Disable filtering.                                                                                                                                              
    subscriber.setsockopt(zmq.SUBSCRIBE, "")                                                                                                                          
    count = 0                                                                                                                                                           
    while True:                                                                                                                                                       
        # Receive raw market JSON strings.                                                                                                                            
        market_json = zlib.decompress(subscriber.recv())                                                                                                              
        # Un-serialize the JSON data to a Python dict.                                                                                                                
        market_data = simplejson.loads(market_json)                                                                                                                   
        # Dump the market data to stdout. Or, you know, do more fun                                                                                                   
        # things here.                                                                                                                                                
        print time.time()                                                                                                                                             
        filename = "/dev/shm/" + str(time.time()) + ".json"                                                                                                           
        with open(filename, "w") as myfile:                                                                                                                           
            myfile.write(str(market_json))                                                                                                                            
        count += 1
        if count > 1000:
            return
                                                                                                                                                        
if __name__ == '__main__':                                                                                                                                            
    main()                                                                                                                                                            

