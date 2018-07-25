<?php
require_once ("config.php");
require_once ("selcom.card.dbhandler.php");


/** 
 *
 * PHP version 5
 *
 * @modal Validate
 * @author   Salma Lalji
 **/
class Transactions
{
    private $reference = null;
    private $conn =null;

    public function __construct() {
        $this->reference = DB::getToken(12);
       
        $this->conn = DB::getInstance();
    }
    public function _pdoBindArray(&$poStatement, &$paArray){
       
        foreach ($paArray as $k=>$v){
              
            @$poStatement->bindValue(':'.$k,$v);
           
      
        } // foreach
        return $poStatement;
      }
    public function _updateAccountProfile($data){

        $payload = (array)$data;
        $arr = null;
        $customer = $this->_getAccountNo($data['customerNo']);
        
        $transid=$data['transid'];
        unset($payload['transid']);
        unset($payload['customerNo']);
        try{
            
            foreach($payload as $key => $val){
                
                    $arr.=$key . '=:'.$key.', ';                    
            }
           
            $arr = rtrim($arr,', ');
           
             
            $sql ="UPDATE accountprofile SET $arr where accountNo = '".$customer."'";
                        
            $stmt = $this->conn->prepare( $sql );
            $state = $this->_pdoBindArray($stmt,$payload);             
            $state->execute();            
            return  $customer;
           

        }catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: updateAccount '.$e->getMessage()." : ".$sql;
            
            $respArray = ['transid'=>$data['transid'],$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
    }
    public function _linkAccountProfile($data){

        $payload = (array)$data;
        $arr = null;
        $customer = $this->_getAccountNo($data['customerNo']);
        
        $transid=$data['transid'];
        unset($payload['transid']);
        unset($payload['customerNo']);
        try{
            
            foreach($payload as $key => $val){
                
                    $arr.=$key . '=:'.$key.', ';                    
            }
           
            $arr = rtrim($arr,', ');
           
             
            $sql ="UPDATE accountprofile SET $arr where accountNo = '".$customer."'";
                        
            $stmt = $this->conn->prepare( $sql );
            $state = $this->_pdoBindArray($stmt,$payload);             
            $state->execute();            
            return  $customer;
           

        }catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: updateAccount '.$e->getMessage()." : ".$sql;
            
            $respArray = ['transid'=>$data['transid'],$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
    }
    public function _addAccountProfile($data){
        //unset($data['transid']);
        $profile = array();
        $profile['firstName']=isset($data['firstName'])?$data['firstName']:'';
        $profile['lastName']=isset($data['lastName'])?$data['lastName']:'';
        $profile['gender']=isset($data['gender'])?$data['gender']:'';
        $profile['customerNo']=isset($data['customerNo'])?$data['customerNo']:'';
        $profile['accountNo']=isset($data['accountNo'])?$data['accountNo']:'';
        $profile['msisdn']=isset($data['msisdn'])?$data['msisdn']:'';
        $profile['email']=isset($data['email'])?$data['email']:'';
        //$profile['status']=isset($data['status'])?$data['status']:0;
        $profile['addressLine1']=isset($data['addressLine1'])?$data['addressLine1']:'';
        $profile['addressCity']=isset($data['addressCity'])?$data['addressCity']:'';
        $profile['addressCountry']=isset($data['email'])?$data['email']:'';
        $profile['dob']=isset($data['dob'])?$data['dob']:'';
        $profile['currency']=isset($data['currency'])?$data['currency']:'TZS';
        $profile['status']=1;
        //$profile['active']=isset($data['active'])?$data['active']:0; //acive=0 closed=1
        $profile['nationality']=isset($data['nationality'])?$data['nationality']:'';
        $profile['balance']=0;
       
        $cols = null;
        $vals = null;
        try{
            
            foreach($profile as $key => $val){
                
                    $cols.=$key.', ';
                    $vals.=':'.$key.', ';
            }
           
            $cols = rtrim($cols,', ');
            $vals = rtrim($vals,', ');
           
            $sql ="INSERT INTO accountprofile (".$cols.") VALUES (".$vals.")";
            $stmt = $this->conn->prepare( $sql );
            $state = $this->_pdoBindArray($stmt,$profile);            
            $state->execute();
        }
        catch (Exception $e) {
           
            $message = array();
            $message['status']="ERROR";
            $message['message']='Transaction error at: _addAccountProfile '.$e->getMessage()." : ".$sql;
            
            $error = 'Transaction error at: _addAccountProfile '.$e->getMessage();
            throw new Exception($error);
        }
        $error = 'Transaction error at: _addAccountProfile ';//.$e->getMessage();
        //throw new Exception($error);
        return false;
        
    }
    public function _getAccountNo($customerNo){
        
        $sql ="select accountNo from accountProfile where customerNo ='$customerNo' || msisdn ='$customerNo' ";  

        $stmt = $this->conn->prepare( $sql );
        $stmt->execute();            
        $result = $stmt->fetchColumn();
        return $result;           
           
    }
    public function _get_msisdn($accountNo){
        
        $sql ="select msisdn from accountProfile where accountNo ='$accountNo' ";  

        $stmt = $this->conn->prepare( $sql );
        $stmt->execute();            
        $result = $stmt->fetchColumn();
       
        return $result;           
           
    }
    public function _getSuspense($customerNo){
        //get accountNo;
        
        $accountNo = $this->_getAccountNo($customerNo);

        $sql ="select suspense from card where id ='$accountNo'";  

        $stmt = $this->conn->prepare( $sql );
        $stmt->execute();            
        $result = $stmt->fetchColumn();
        return $result;           
           
    }
    public function _getProfile($accountNo){
        
        $sql ="select * from accountProfile where accountNo ='$accountNo' ";  

        $stmt = $this->conn->prepare( $sql );
        $stmt->execute();            
        $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
        return json_encode($result);
           
    }
    public function _setResponse($method){
        $arr = array();
        $arr['reference']=$this->reference;
        $arr['fulltimestamp'] = date('Y-m-d H:i:s');
        //$arr['transid'] = $data->transid;
        $arr['message'] = $method;
        return $arr;
    }
    public function _addCard($data)   {
        
        /*
        fulltimestamp create
        Name concat fname lname
        Msisdn required
        Card required
        Status default = 0         
        Dealer = Transsnet
        Reference create
        Email if, else ''
        Phone if, else ''
        language default

        */
       #old
		// review active=1 here, we need to update once user has verified
        // $query = "UPDATE card SET msisdn='$msisdn', status='1', fulltimestamp=NOW(),registeredby='SYSTEM', confirmedby='SYSTEM', registertimestamp = NOW(), confirmtimestamp=NOW(), active='0' WHERE card='$cardnum'";

		// $this->pdo_db->query($query);

		// generate sms code
        $request = (array)$data;
       
        $payload = array();
        $payload['fulltimestamp'] = $request['fulltimestamp'];
        $payload['accountNo'] = $request['accountNo'];
        $payload['name'] = $request['firstName']. ' '.$request['lastName'];
        $payload['msisdn'] = $request['msisdn'];
        //$payload['card'] = isset($request['card'])?$request['card']:'';
        $payload['dealer'] = 'TRANSSNET';
        $payload['registeredby'] = 'SelcomTranssetAPI';
        $payload['confirmedby'] = 'SelcomTranssetAPI';
        $payload['registertimestamp'] =$request['fulltimestamp'];
        $payload['confirmtimestamp'] = $request['fulltimestamp'];
        //$payload['active'] = 0;
        $payload['status'] = 1;        
        $payload['reference'] = $this->reference;//request['reference'];
        $payload['email'] = isset($request['email'])?$request['email']:'';
        $payload['phone'] = isset($request['phone'])?$request['phone']:'';
        //$payload['message'] = $request['message'];       
        
       
        $cols = null;
        $vals = null;
        $sql =null;
        try{
            
            foreach($payload as $key => $val){
                
                    $cols.=$key.', ';
                    $vals.=':'.$key.', ';
            }
            $cols = rtrim($cols,', ');
            $vals = rtrim($vals,', ');
            
            $sql ="INSERT INTO card (".$cols.") VALUES (".$vals.")";
            $stmt = $this->conn->prepare( $sql );
            $state = $this->_pdoBindArray($stmt,$payload);
                     
            $state->execute();
            $id = $this->conn->lastInsertId();
           
            $message = array();
            $message['status']="SUCCESS";
            //$message['message']="add Card";
            return $id;
           
            //$respArray = ['transid'=>$data->transid,'reference'=>$this->reference,'responseCode' => 200, "Message"=>($message)];
        }
        catch (Exception $e) {
           
            $message = array();
            $message['status']="ERROR";
            $message['message']='Transaction error at: addcard '.$e->getMessage()." : ".$payload['msisdn'] .' : '. $request['msisdn'];;
            
            $error = 'Transaction error at: addcard '.$e->getMessage()." : ".$payload['msisdn'] .' : '. $request['msisdn'];
            throw new Exception($error);
        }
        $error = 'Transaction error at: addcard '.$e->getMessage();
        throw new Exception($error);
        return -1;
        
    }
    public function _addTransaction($data)   {
        /*
        fulltimestamp same as card	
        transid required	
        reference	same as card
        msisdn	required
        message	openAccount
        */
        
        /*$payload = (array)$data;
        $payload = array();
        $payload['fulltimestamp'] = $data['fulltimestamp'];
        $payload['transid'] = $data['transid'];
        $payload['reference'] = $data['reference'];
        $payload['card'] = isset($data['card'])?$data['card']:'';
        $payload['msisdn'] = isset($data['msisdn'])?$data['msisdn']:'';
        $payload['message'] = $data['message'];
        $payload['channel'] = 'TPAY';
        
        $payload['card']=isset($data['customerNo'])?$data['customerNo']:'';
        $payload['message']=isset($data['message'])?$data['message']:'';
        $payload['utilityref']=isset($data['toAccountNo'])?$data['toAccountNo']:'';
        //unset($data['customerNo']);    
        unset($data['toAccountNo']);  
        unset($data['currency']);
        //unset($data['method']); 
        unset($data['transid']);
        unset($data['transType']);  //add this to another table say transsnet_log  
         */
        $cols = null;
        $vals = null;
        try{
            foreach($data as $key => $val){
                
                $payload[$key]=$val;
              
            }
            $payload['channel'] = 'PALMPAY';
            foreach($payload as $key => $val){
                
                    $cols.=$key.', ';
                    $vals.=':'.$key.', ';
            }
            $cols = rtrim($cols,', ');
            $vals = rtrim($vals,', ');
           
            $sql ="INSERT INTO transaction (".$cols.") VALUES (".$vals.")";
            $stmt = $this->conn->prepare( $sql );
            $state = $this->_pdoBindArray($stmt,$payload);            
            $state->execute();
           
            $message = array();
            $message['status']="SUCCESS";
            $message['method']="_addTransaction";
            return true;
           
            //$respArray = ['transid'=>$data->transid,'reference'=>$this->reference,'responseCode' => 200, "Message"=>($message)];
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: addTransaction '.$e->getMessage()." : ".$sql;
            $error = 'Transaction error at: addTransaction '.$e->getMessage();
            throw new Exception($error);
        }
        $error = 'Transaction error at: addTransaction '.$e->getMessage().' '.$sql;;
        throw new Exception($error);
        return false;
        
        
    }
        /**
 * Checks a 16 digit card number whether the checksum is Luhn-approved.
 * If $create is set to true, return the input + the checksum.
 * @param type $card
 * @param type $create
 * @return mixed
 */
function _checkLuhn($card, $create = false){
    $segments = str_split($card, 15);
    $digits = str_split($segments[0], 1);
    foreach ($digits as $k => $d) {
        if ($k % 2 == 0) {
            $digits[$k] *= 2;
            if (strlen($digits[$k]) > 1) {
                $split = str_split($digits[$k]);
                $digits[$k] = array_sum($split);
            }
        }
    }
    $digits = array_sum($digits)*9;
    $digits = str_split($digits);
    $checksum = $digits[max(array_keys($digits))];
    
    if ($create == false) {
        if (!isset($segments[1])) {
            return "Invalid input length.";
        }
        if ($checksum == $segments[1]) {
            return 1;
        } else {
            return 0;
        }
    } else {
        return $segments[0].$checksum;
    }
}
public function  _checkTcard($accountNo){
    $sql = 'select id from tcard where accountNo="'.$accountNo.'"';
    $stmt = $this->conn->prepare( $sql );
    $stmt->execute();
    $result = $stmt->fetchAll();
    //die ('here'.print_r($result));
    return $result;

}
    public function openAccount($data)   {
        //check for required fields eg accountNo, msisdn, fname lname
        $err = Validate::openAccount($data); //if 2 MJ then account alread exists status is still 0 change it to one on accountprofile
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);
        try{

            $payload = (array)$data;    
            $payload['accountNo'] = 'PALMPAY'.DB::getToken(12);       
            $dob=DB::toDate($payload['dob']);                        
            $payload['reference']=$this->reference;
            $payload['fulltimestamp'] = date('Y-m-d H:i:s');            
            

            //add to card DB
            $last_id = $this->_addCard($payload);// active = 0 not happening
            if ($last_id < 0){
                $error = 'Transaction error at: _addCard '.$e->getMessage();
                throw new Exception($error);
            }
           
            //add to transactions DB
            //$this->addTransaction($payload);
            //$payload['accountNo'] = $last_id;
            //$payload['accountNo'] = 'PALMPAY' . 
            //add to accountProfile DB
            $this->_addAccountProfile($payload);
            $message = array();
            $message['status']="SUCCESS";
            $message['method']="openAccount";
            $message['data']=$payload;
            
            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => 200, "Message"=>($message)];
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: openAccount '.$e->getMessage()." : ";
            
            $respArray = ['transid'=>$data->transid,$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
        
        return (json_encode($respArray));
    }
    public function updateAccount($data)   {
        $err = Validate::updateAccount($data); //if 2 MJ then account alread exists status is still 0 change it to one on accountprofile
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);

        $payload = (array)$data;
        $tier =isset($payload['tier'])?strtoupper($payload['tier']):'A';
       
        //update accountProfile DB
        $customer = $this->_updateAccountProfile($payload);
        $payload['accountNo']=$customer;
        if(isset($tier)){
        
            switch($tier){
                case 'B': $tier='B'; break;
                case 'C': $tier='C'; break;
                case 'D': $tier='D'; break;
                default: 'A';
            }
            
            $query = "UPDATE card SET tier='$tier' WHERE id=$customer";
            $this->conn->query($query);
        }
           

        $payload['reference']=$this->reference;
        $payload['fulltimestamp'] = date('Y-m-d H:i:s');
        //$payload['transid'] = $data->transid;
        $payload['method'] = 'updateAccount';
        unset($payload['transid']) ;
    
        $message = array();
        $message['status']="SUCCESS";
        $message['method']="updateAccount";
        $message['data']=$payload;
        $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => 200, "Message"=>($message)];
    
        return (json_encode($respArray));
    }
    public function linkAccount($data)   {
        $err = Validate::linkAccount($data); //if 2 MJ then account alread exists status is still 0 change it to one on accountprofile
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);

        $payload = (array)$data;
        $bname = $payload['bankname'];
        $bbranch = $payload['bankbranch'];
        $baccountname = $payload['bankaccountname'];
        $baccountno = $payload['bankaccountnumber'];
        
        $customer = $this->_getAccountNo($payload['customerNo']);
        try{
            
            $query = "UPDATE accountprofile SET bankname='".$bname."', bankbranch='".$bbranch."', bankaccountname='".$baccountname."', bankaccountnumber='".$baccountno."' WHERE accountNo=$customer";
            $this->conn->query($query);        
           

            $payload['reference']=$this->reference;
            $payload['fulltimestamp'] = date('Y-m-d H:i:s');
            //$payload['transid'] = $data->transid;
            $payload['method'] = 'linkAccount';
            unset($payload['transid']) ;
        
            $message = array();
            $message['status']="SUCCESS";
            $message['method']="linkAccount";
            $message['data']=$payload;
            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => 200, "Message"=>($message)];
        
            }catch (Exception $e) {
                
                $message = array();
                $message['status']="ERROR";
                $message['method']='Transaction error at: linkAccount '.$e->getMessage()." : ".$customer;
                
                $respArray = ['transid'=>$data->transid,'reference'=>$response['reference'],'responseCode' => 501, "Message"=>($message)];
            }
        return (json_encode($respArray));
    }
    public function unLinkAccount($data)   {
        $err = Validate::linkAccount($data); //if 2 MJ then account alread exists status is still 0 change it to one on accountprofile
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);

        $payload = (array)$data;
        $bname = $payload['bankname'];
        $bbranch = $payload['bankbranch'];
        $baccountname = $payload['bankaccountname'];
        $baccountno = $payload['bankaccountnumber'];
        
        $customer = $this->_getAccountNo($payload['customerNo']);
        try{
            
            
            $sql = "select bankname, bankbranch, bankaccountname, bankaccountnumber from  accountprofile WHERE accountNo=$customer";
            $stmt = $this->conn->prepare( $sql );
            $stmt->execute();            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $row = $result[0];
                  
           
            //verify the information is the same as in DB
            $bname = $row['bankname'] === $payload['bankname']?'':'999';
            $bbranch = $row['bankbranch'] === $payload['bankbranch']?'':'999';
            $baccountname = $row['bankaccountname'] === $payload['bankaccountname']?'':'999';
            $baccountno = $row['bankaccountnumber'] === $payload['bankaccountnumber']?'':'999';
            // 
            if (empty($bname) && empty($bbranch) && empty($baccountname) && empty($baccountno)){
                $query = "UPDATE accountprofile SET bankname='".$bname."', bankbranch='".$bbranch."', bankaccountname='".$baccountname."', bankaccountnumber='".$baccountno."' WHERE id=$customer";
                $this->conn->query($query);

            }
            else{
                $err ="account information could not be verified, please check your details ";
                throw new Exception($err);
            }
         
           

            $payload['reference']=$this->reference;
            $payload['fulltimestamp'] = date('Y-m-d H:i:s');
            //$payload['transid'] = $data->transid;
            $payload['method'] = 'linkAccount';
            unset($payload['transid']) ;
        
            $message = array();
            $message['status']="SUCCESS";
            $message['method']="linkAccount";
            $message['data']="successfully unlinked Account from TPAY";
            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => 200, "Message"=>($message)];
        
        }catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: unLinkAccount '.$e->getMessage()." : ";
            
            $respArray = ['transid'=>$data->transid,'reference'=>$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
    }
    public function nameLookup($data)   {
        //check for required fields eg transid return accountprofile info of this transid 
        
        $err = Validate::nameLookup($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);
        $payload = (array)$data;    
        $response = $this->_setResponse('nameLookup');
        
        $where =null;
        try{
            
            if(empty($payload['accountNo'])){
                $where .= isset($payload['customerNo'])?'where customerNo="'.$payload['customerNo'].'"':' where msisdn="'.$payload['msisdn'].'"';
            }
            else 
                $where = "where accountNo=".$payload['accountNo'];

            $sql ='SELECT firstName, lastName, tier,customerNo,accountNo, msisdn,  REPLACE(REPLACE(status,0,\'false\'),1,\'true\') AS statustxt, REPLACE(REPLACE(active,0,\'true\'),1,\'false\') AS activetxt, email, addressLine1,addressCity, addressCountry,dob as dateofbirth,state, gender, nationality, currency, balance, lastupdated from accountprofile '.$where;
          
            $stmt = $this->conn->prepare( $sql );
            $state = $this->_pdoBindArray($stmt,$payload);            
            $state->execute();            
          
            $result = $state->fetchAll(PDO::FETCH_ASSOC);
            
            //$json = json_encode($arr);
            
            $message = array();
            $message['status']="SUCCESS";
            $message['method']="nameLookup";
            $message['data']=$result;
            if (empty($result)){
                $error="account does not exist";
                throw new Exception($error);
            }
            $respArray = ['transid'=>$data->transid,'reference'=>$response['reference'],'responseCode' => 200, "Message"=>($message)];
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: nameLookup '.$e->getMessage()." : ";//.$sql;
            
            $respArray = ['transid'=>$data->transid,'reference'=>$response['reference'],'responseCode' => 501, "Message"=>($message)];
        }
        //die(print_r($sql));
        return (json_encode($respArray));
    }
    public function transactionLookup($data)   {
        //check for required fields eg transid return accountprofile info of this transid 
        $err = Validate::transactionLookup($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);
        $payload = (array)$data;
        $msisdn = isset($payload['msisdn'])?$payload['msisdn']:$this->_get_msisdn($payload['accountNo']);        
       
       
        //select * from accountprofile join transaction on accountprofile.card = transaction.card where transid = 'TPAY01052018161000'
        try{
             /*transRef is the needle transid */
            $sql ="select * from transaction where transid = '".$data->transref."' && msisdn='$msisdn'";
          
            $stmt = $this->conn->prepare( $sql );
            $state = $this->_pdoBindArray($stmt,$payload);            
            $state->execute();
            $result = $state->fetchAll(PDO::FETCH_ASSOC);            
           
            $payload['reference']=$this->reference;
          
            $payload['fulltimestamp'] = date('Y-m-d H:i:s');
            $payload['transid'] = $data->transid;
            $payload['message'] = 'transactionLookup';
            
            $message = array();
            $message['status']="SUCCESS";
            $message['method']="transactionLookup";
            $message['data']=$result;
        
            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => 200, "Message"=>($message)];
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: transactionLookup '.$e->getMessage()." : ";//.$sql;
            
            $respArray = ['transid'=>$data->transid,$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
    }
    public function transferFunds($data)   {
       
        //check for required fields eg transid return accountprofile info of this transid 
        $err = Validate::transferFunds($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);        
        $payload = (array)$data;
        $msisdn = isset($payload['msisdn'])?$payload['msisdn']:$this->_get_msisdn($payload['accountNo']);
               
        try{             
           
            $payload['reference']=$this->reference;
            $payload['utilityref'] = $payload['toAccountNo'];
           
            //save extra info into tinfo table
            if(!Validate::setTinfo($payload)){
                $err="Internal Error 500";
                throw new Exception($err);
            }


            unset($payload['toAccountNo']);
            unset($payload['currency']);
            

            $payload['fulltimestamp'] = date('Y-m-d H:i:s');
            $payload['message'] = 'transferFunds';

            

            $selcom = new DbHandler();
            $result = $selcom->fundTransfer($payload['transid'],$payload['reference'],$payload['utilityref'], $msisdn,$payload['amount']);
            //die(print_r($result));
                $message = array();
                $message['status']= $result['resultcode'] =='000'?'SUCCESS':'ERROR';
                $message['method']="transferFunds";
                $message['data']=$result;
                $code = $result['resultcode'] =='000'?200:501;

            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => $code, "Message"=>($message)];
        
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: transferFunds '.$e->getMessage()." : ".$e;
            
            $respArray = ['transid'=>$data->transid,$payload['reference'],'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
    }
    public function checkBalance($data)   {
        //check for required fields eg transid return accountprofile info of this transid 
        $err = Validate::enquiry($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);        
        $payload = (array)$data;    
        
        try{             
           
            $payload['reference']=$this->reference;
            $account = isset($payload['customerNo'])?$payload['customerNo']:$payload['msisdn'];
            unset($payload['transid']);
            $payload['fulltimestamp'] = date('Y-m-d H:i:s');
            $payload['transid'] = $data->transid;
            $payload['message'] = 'checkBalance';
            //add to card DB updateCard ?
            //$this->updateCard($payload);          

                    
                        
            $selcom = new DbHandler();
            $result = $selcom->balanceEnquiry($account);
            foreach ($result as $key => $val){
                $payload[$key]=$val;
            }
            
            $message = array();
            $message['status']= $result['resultcode'] =='000'?'SUCCESS':'ERROR';
            $message['method']="checkBalance";
            $message['data']=$payload;
            $code = $result['resultcode'] =='000'?200:501;

            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => $code, "Message"=>($message)];
        
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: checkBalance '.$e->getMessage()." : ".$sql;
            
            $respArray = ['transid'=>$data->transid,$payload['reference'],'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
    }
    public function getStatement($data)   {
        //check for required fields eg transid return accountprofile info of this transid 
        $err = Validate::enquiry($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);        
        $payload = (array)$data;    
        
        try{             
           
            $payload['reference']=$this->reference;
            $account = isset($payload['customerNo'])?$payload['customerNo']:$payload['msisdn'];
            $days = isset($payload['days'])?$payload['days']:3;
            unset($payload['transid']);
            $payload['fulltimestamp'] = date('Y-m-d H:i:s');
            $payload['transid'] = $data->transid;
            $payload['message'] = 'getStatement';
            //add to card DB updateCard ?
            //$this->updateCard($payload);          

                    
                        
            $selcom = new DbHandler();
            $result = $selcom->statementEnquiry($account,$days);
           
            foreach ($result as $key => $val){
                $payload[$key]=$val;
            }
            
            $message = array();
            $message['status']= 'SUCCESS';
            $message['method']="getStatement";
            $message['data']=$payload;
            $code = '200';

            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' =>  $code, "Message"=>($message)];
        
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: getStatement '.$e->getMessage()." : ".$sql;
            
            $respArray = ['transid'=>$data->transid,$payload['reference'],'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
    }
    public function updateAccountStatus($data){
        //check for required fields eg transid return accountprofile info of this transid 
       $err = Validate::accountState($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);        
        $payload = (array)$data; 
         
                 
        $payload = (array)$data;
        $payload['reference']=$this->reference;  
        $account = isset($payload['customerNo'])?$payload['customerNo']:$payload['msisdn'];
        $customer = $this->_getAccountNo($account);
        $status = isset($payload['statustxt']) && $payload['statustxt'] === 'open'?'1':'0';
        $payload['accountNo']=$customer;
        unset( $payload['transid']);
                   
        try{
            $sql = "UPDATE card SET status='".$status."' where id='".$customer."'; ";
            $sql2 =" UPDATE accountprofile SET status='".$status."' where accountNo='".$customer."'";
            
            $stmt = $this->conn->query($sql);
            $stmt->execute();

            $stmt = $this->conn->query($sql2);
            $stmt->execute();

            //$result = $this->_getProfile($customer);
            
           //(print_r($result));

            $message = array();
            $message['status']="SUCCESS";
            $message['method']="updateAccountStatus";
            $message['data']=$payload;

            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' => 200, "Message"=>($message)];
        
           

        }catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: updateAccountStatus '.$e->getMessage()." : ".$sql;
            
            $respArray = ['transid'=>$data->transid,$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
        error_log("Oracle database not available!", 0);
        return json_encode($respArray);
    }
   
    public function cashin($data){
        //check for required fields eg transid return accountprofile info of this transid 
       $err = Validate::cashin($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);        
        $payload = (array)$data; 
        
                 
        $payload = (array)$data;
        $payload['reference']=$this->reference;  
        $customer = isset($payload['accountNo'])?$payload['accountNo']:$this->_getAccountNo($payload['msisdn']);
        
        $profile = json_decode($this->_getProfile($customer), true);
        //$msisdn_acct = $profile[0]['msisdn'];
        $name =  $profile[0]['firstName'].' '. $profile[0]['lastName']; 
        $amount = $payload['amount'];
        $transid = $payload['transid'];
        //$msisdn = $payload['msisdn'];   
        //die(print_r($payload));

        //$payload['accountNo']=$customer;
        unset( $payload['transid']);
                   
        try{
           
            $selcom = new DbHandler();
            $result = $selcom->utilityPayment($transid,'SPCASHIN',$payload['utilityref'],$payload['msisdn'],$amount,$payload['reference']);            
           

            $message = array();
            $message['status']= $result['resultcode'] =='000'?'SUCCESS':'ERROR';
            $message['method']="cashin";
            $message['data']=$result;
            $code = $result['resultcode'] =='000'?200:501;

            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' =>  $code, "Message"=>($message)];
        
           

        }catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: cashin '.$e->getMessage();
            
            $respArray = ['transid'=>$data->transid,$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
        return json_encode($respArray);
    }
    public function payUtility($data){
        //check for required fields eg transid return accountprofile info of this transid 
       $err = Validate::payUtility($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);        
        $payload = (array)$data; 
        
                 
        $payload = (array)$data;
        $payload['reference']=$this->reference;  
        $account = isset($payload['customerNo'])?$payload['customerNo']:$payload['msisdn'];
        $customer = $this->_getAccountNo($account);
        $profile = json_decode($this->_getProfile($customer), true);
        $name =  $profile[0]['firstName'].' '. $profile[0]['lastName'];
        $utilitycode = $payload['utilitycode'];
        $utilityref = $payload['utilityref'];
        $amount = $payload['amount'];
        $transid = $payload['transid'];
        $msisdn = $payload['msisdn'];

        $payload['accountNo']=$customer;
        unset( $payload['transid']);
                   
        try{
           
            $selcom = new DbHandler();
            $result = $selcom->utilityPayment($transid,$utilitycode,$utilityref,$msisdn,$amount,$payload['reference']);            
           

            $message = array();
            $message['status']= $result['resultcode'] =='000'?'SUCCESS':'ERROR';
            $message['method']="payUtility";
            $message['data']=$result;
            $code = $result['resultcode'] =='000'?200:501;

            $respArray = ['transid'=>$data->transid,'reference'=>$payload['reference'],'responseCode' =>  $code, "Message"=>($message)];
        
           

        }catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: payUtility '.$e->getMessage()." : ".$sql;
            
            $respArray = ['transid'=>$data->transid,$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
        return json_encode($respArray);
    }
    public function reserveAccount($data)   {
        //check for required fields eg transid return accountprofile info of this transid 
        
       $err = Validate::reserveAccount($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);
        
        $payload = (array)$data;
        $ref=$this->reference;  
        $account = isset($payload['customerNo'])?$payload['customerNo']:$payload['msisdn'];
        $customer = $this->_getAccountNo($account);
        $amount = $payload['amount'];
       
        $transid = $payload['transid'];
        $msisdn = $payload['msisdn'];
        $utilityref = $payload['msisdn'];//same account
       
        try{
            //create transaction to debit card of the amount added to suspense
            $selcom = new DbHandler();            
            $result = $selcom->reserveAccount($transid,$ref,$msisdn,$amount);

           //die(print_r($result));
            $message = array();
            $message['status']= $result['resultcode'] =='000'?'SUCCESS':'ERROR';
            $message['method']="reserveAmount";
            $message['data']=$result;
            $code = $result['resultcode'] =='000'?200:501;
            
            $respArray = ['transid'=>$data->transid,'reference'=>$ref,'responseCode' =>  $code, "Message"=>($message)];
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: reserveAmount '.$e." : ";//.$sql;
            
            $respArray = ['transid'=>$data->transid,'reference'=>$ref,'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
    }
    public function unReserveAccount($data)   {
         //check if the reference is the same as on in the transaction
        
       $err = Validate::unReserveAccount($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);

        $payload = (array)$data;
        $ref=$this->reference;  
        $account = isset($payload['customerNo'])?$payload['customerNo']:$payload['msisdn'];
        $customer = $this->_getAccountNo($account);
        $amount = $payload['amount'];
        $transid = $payload['transid'];
        $msisdn = $payload['msisdn'];
        $utilityref = $payload['msisdn'];//same account
       
        try{
            //create transaction to debit card of the amount added to suspense
            $selcom = new DbHandler();            
            $result = $selcom->unReserveAccount($transid,$ref,$payload['reference'],$msisdn,$amount);
            $message = array();
            $message['status']= $result['resultcode'] =='000'?'SUCCESS':'ERROR';
            $message['method']="unReserveAccount";
            $message['data']=$result;
            $code = $result['resultcode'] =='000'?200:501;
            
            $respArray = ['transid'=>$data->transid,'reference'=>$ref,'responseCode' =>  $code, "Message"=>($message)];
        }
        catch (Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='Transaction error at: unReserveAccount '.$e." : ";//.$sql;
            
            $respArray = ['transid'=>$data->transid,'reference'=>$ref,'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
    }
    public function requestCard($data){
        $err = Validate::requestCard($data);
        if (!empty($err))
            return DB::getErrorResponse($data, $err, $this->reference);
        try{
            
            if (empty($this->_checkTcard( $data->accountNo))){
                $request = (array)$data;
                $today=date('Y-m-d H:i:s');;
                $payload = array();
                $payload['fulltimestamp'] = $today;
                //$payload['customerNo'] = $request['customerNo'];
                $payload['accountNo'] = $request['accountNo'];
                $payload['name'] = $request['name'];
                $payload['msisdn'] = $request['msisdn'];
                $card = DB::getToken(16);
                //$i=0;
                do {
                    //echo $i++;
                    $card = DB::getToken(16);
                } while ($this->_checkLuhn($card));
                //die ($card.' : '.$this->_checkLuhn($card));
                
            
                $payload['card'] = $card;
                $payload['cvs'] = DB::getToken(3);
                $payload['exp'] = DB::getToken(2).'/'.rand(2020,2027);
                $payload['dealer'] = 'Transsnet';
                
                $payload['registeredby'] = 'SelcomTranssetAPI';
                $payload['confirmedby'] = 'SelcomTranssetAPI';
                $payload['registertimestamp'] = $today;
                $payload['confirmtimestamp'] =  $today;
                $payload['active'] = 0;
                $payload['status'] = 1;        
                $payload['reference'] = $this->reference;//request['reference'];
                $payload['email'] = isset($request['email'])?$request['email']:'';
                $payload['phone'] = isset($request['phone'])?$request['phone']:'';
                //$payload['message'] = $request['message'];       
                
            
                $cols = null;
                $vals = null;
                $sql =null;
            
                    
                foreach($payload as $key => $val){
                    
                        $cols.=$key.', ';
                        $vals.=':'.$key.', ';
                }
                $cols = rtrim($cols,', ');
                $vals = rtrim($vals,', ');
                
                 
                $sql ="INSERT INTO tcard (".$cols.") VALUES (".$vals.")";
                $stmt = $this->conn->prepare( $sql );
                $state = $this->_pdoBindArray($stmt,$payload);                        
                $state->execute();

                $id = $this->conn->lastInsertId();
                $message = array();
                $message['status']='000';
                $message['method']="requestCard";
                $message['data']=$payload;
                $code = '200';
                
                $respArray = ['transid'=>$data->transid,'reference'=>$this->reference,'responseCode' =>  $code, "Message"=>($message)];
            }
            else{
                $err ="card Already Exists";
                throw new Exception($err);
            }

                
        }
        catch(Exception $e) {
            
            $message = array();
            $message['status']="ERROR";
            $message['method']='requestCard' ;
            $message['data']='Transaction error at: requestCard '.$e->getMessage();
            
            $respArray = ['transid'=>$data->transid,'reference'=>$this->reference,'responseCode' => 501, "Message"=>($message)];
        }
        return (json_encode($respArray));
        
    }
    public function search($data){
        //die('here '.$data->search);
        
        $searchthis = $data->search;
        $matches = array();

        $handle = @fopen("transsetlog.log", "r");
        if ($handle)
        {
            while (!feof($handle))
            {
                $buffer = fgets($handle);
                if(strpos($buffer, $searchthis) !== FALSE)
                    $matches[] = $buffer;
            }
            fclose($handle);
        }

        //show results:
        return $matches;
        
    }
   

}
