	
    function getXMLHttpRequest(){
		var req = false;
		try{
			/* Firefox용 */
			req = new XMLHttpRequest();
		}catch(err){
			try{
				/* IE의 일부 버전용 */
				req = new ActiveXObject("Msxml2.XMLHTTP");
			}catch(err){
				try{
					/* IE의 다른 버전용 */
					req = new ActiveXObject("Microsoft.XMLHTTP");
				}catch(err){
					req = false;
				}
			}
		}
		
		return req;
	}
    
    function ajaxAction(method, url, params){
    	myReq.open(method, url, true);
    	myReq.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;UTF-8");
    	myReq.setRequestHeader("Content-Length", params.length);
    	myReq.setRequestHeader("Connection","close");
    	myReq.onreadystatechange = ajaxResponseText;
    	myReq.send(params);
    }
    
    function ajaxResponseText(){
    	if(myReq.readyState == 4 && myReq.status == 200){
    		var result = myReq.responseText;
    		eval(result);
    	}
    }    
