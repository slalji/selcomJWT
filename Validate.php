<?php
require_once ("config.php");
require_once ("DB.php");
/**
 * Validates Selcom API Payload of
 * {
*	"iss": APP_NAME,
*	"method": "reserveAmount",
*	"timestamp": "1529998743",
*	"requestParams": {
*		"transid": "580929745048",
*		"String": "String",
*		"String": "String",
*		"String": "String",
*		"String":"String",
*		"currency": "TZS"
*
*	}
*}
*
 *
 * PHP version 5
 *
 * @modal Validate
 * @author   Salma Lalji
 **/
class Validate
{
    public static function valid($payload)    {

        $err = array();
        if (!isset($payload->iss) || empty($payload->iss)) {
            $err[]='parameter issuer "iss" may not be empty';
        }
        if (!isset($payload->timestamp) || empty($payload->timestamp )) {
            $err[]='parameter "timestamp" may not be empty';
        }
        if (isset($payload->timestamp) && !is_numeric((int)$payload->timestamp ) ) {
            $err[]='parameter "timestamp" must be in numeric timestamp format ' .$payload->timestamp ;
        }
        if (!isset($payload->method) || empty($payload->method)) {

            $err[]='parameter "method" may not be empty';
        }
        if (!isset($payload->requestParams) || empty($payload->requestParams)) {
            $err[]='request paramaters may not be empty';
        }
        if (!isset($payload->requestParams->transid) || empty($payload->requestParams->transid)) {
            $err[]='transid request paramater may not be empty ';
        }
        $data='';
        foreach($err as $e)
            $data .=$e.'';


        return ($data);
    }

    public static function verify($headers){
        try {
            /*
             * Look for the 'authorization' header
             */
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];

            //if (isset($headers['Authorization']) || isset($headers['authorization'])) {



            if ($authHeader) {
                /*
                 * Extract the jwt from the Bearer
                 */
                list($bearer) = sscanf( $authHeader, 'Bearer %s');
                $bearer = explode(',',$bearer)[0];
                $bearer = str_replace('"','',$bearer);


                if ($bearer) {

                    $publicKey = file_get_contents('rsa_public_key.pem', true);
                // if JWT invalid throw exception
                    JWT::decode($bearer, $publicKey, array('RS256'));
                    return true;
                }
                else{
                    header('HTTP/1.0 401 Unauthorized');
                    //echo('HTTP/1.0 401 Unauthorized'/*.$e*/);
                    $err ="Authentication Invalid";
                    throw new Exception($err);
                }
            }

            else{
                header('HTTP/1.0 401 Unauthorized');
                //echo('HTTP/1.0 401 Unauthorized'/*.$e*/);
                $err ="Authentication missing";
                throw new Exception($err);
            }
        /*}
        else{
            header('HTTP/1.0 401 Unauthorized');
            //echo('HTTP/1.0 401 Unauthorized');
            $err ="Authentication missing";
                        throw new Exception($err);
    }*/
    }
    catch (Exception $e) {
        /*
            * the token was not able to be decoded.
            * this is likely because the signature was not able to be verified (tampered token)
            */
        header('HTTP 1.0 401 Unauthorized');
        //echo('HTTP/1.0 401 Unauthorized'/*.$e*/);
        //echo ' Caught exception: ',  $e->getMessage(), "\n";
        $message = array();
            $message['status']="ERROR";
            $message['method']='';//.$e." : ";//.$sql;
            $result['resultcode'] ='401';
            $result['result']=$e->getMessage();
            $message['data']=$result;

        $respArray = ['transid'=>'','reference'=>'','responseCode' => 401, "Message"=>($message)];
        return false; //echo json_encode($respArray);
        //echo json_encode($response = ["transid"=>"","reference"=>"","responseCode"=>"401","Message"=>["status"=>"ERROR","method"=>"","data"=>"HTTP 1.0 401 Unauthorized"]]);


    }

}





    public static function check($acctNo){
        $db = new DB();
        $sql ="select id from accountProfile where accountNo='".$acctNo."'";
        $stmt = $db->conn->prepare( $sql );
        $stmt->execute();
        if ($stmt->rowCount() > 0)
            return true;
        return false;

    }
    public static function _getAccountNo($customerNo){
        $db = new DB();
        $sql ="select accountNo from accountProfile where customerNo ='$customerNo' || msisdn ='$customerNo' ";

        $stmt = $db->conn->prepare( $sql );
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result;

    }
    public static function _getSuspense($accountNo){
        //get accountNo;
        $db = new DB();
        //$accountNo = Validate::_getAccountNo($customerNo);

        $sql ="select suspense from card where id ='$accountNo'";

        $stmt = $db->conn->prepare( $sql );
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result;

    }
    public static function _checkRef($payload){
        //get accountNo;
        $db = new DB();
        $ref = $payload->reference;
       $flag=true;
        try{

            $sql ="select utilitycode, utilityref, dealer, amount from transaction where reference ='$ref'";

            $stmt = $db->conn->prepare( $sql );
            $stmt->execute();
            $rows = $stmt->fetchAll( PDO::FETCH_ASSOC );

            if(!$rows)
               return false;
            $result = ($rows[0]);


            if($result['utilitycode'] != 'reserveAccount'){
                $flag=false;
            }
            else if ($result['utilityref'] != $payload->msisdn){
                $flag=false;
            }
            else if ($result['dealer'] !='TRANSSNET'){
                $flag=false;
            }
            else if ($result['amount'] !=$payload->amount){
                $flag=false;
            }

        }
        catch(Exception $e){
            $flag=false;
            //return false;
        }
        //die(print_r((int)$flag));
        return $flag;

    }

    public static function setTinfo($payload){

        //get accountNo;
        $db = new DB();
        $arr =array();
        $col =null;
        $value =null;


        try{
            $arr['transid'] = isset($payload['transid'])?$payload['transid']:'';
            $arr['reference'] = isset($payload['reference'])?$payload['reference']:'';
            $arr['transtype'] = isset($payload['transtype'])?$payload['transtype']:'';
            $arr['geocode'] = isset($payload['geocode'])?json_encode($payload['geocode']):'';
            $arr['generateVoucher'] = isset($payload['generateVoucher'])?$payload['generateVoucher']:'';
            $arr['redeemVoucher'] = isset($payload['redeemVoucher'])?$payload['redeemVoucher']:'';
            foreach($arr as $key => $val){
                if ($val){
                    $col.=$key .',';
                    $value.="'".$val."'".',';
                }

            }
            $col = rtrim($col,',');
            $value = rtrim($value,',');
            $sql ="INSERT INTO tinfo ($col) VALUES ($value)";

            $stmt = $db->conn->prepare( $sql );
            $stmt->execute();

            unset($payload['transtype']);
            unset($payload['geocode']);
            unset($payload['generateVoucher']);
            unset($payload['redeemVoucher']);
            return $payload;
        }
        catch (Exception $e) {
            return $e->getMessage();
            return false;
        }

    }
    public static function _checkTransid($transid){

        $data = isset($err) ? $err :false;
            $db = new DB();
            $sql ="select id, transid  from transaction where transid='".$transid."'";
            $stmt = $db->conn->prepare( $sql );
            $stmt->execute();
            $result = $stmt->fetchAll();
            return $stmt->rowCount();

    }
    public static function openAccount($payload){

        $err = array();
        try{

            if (!isset($payload->transid) || empty($payload->transid)) {
                $err[]='transid may not be empty';
            }
            if (!isset($payload->customerNo) || empty($payload->customerNo)) {
                $err[]='customerNo may not be empty';
            }
            if (!isset($payload->firstName) || empty($payload->firstName )) {
                $err[]='firstName may not be empty';
            }
            if (!isset($payload->lastName) || empty($payload->lastName )) {
                $err[]='lastName may not be empty';
            }
            if (!isset($payload->msisdn) || empty($payload->msisdn)) {
                $err[]='msisdn may not be empty';
            }


            $db = new DB();
            $sql ="select id from accountProfile where customerNo='".$payload->customerNo."' || msisdn='".$payload->msisdn."'";
            $stmt = $db->conn->prepare( $sql );
            $stmt->execute();
            if ($stmt->rowCount() > 0){
                $err[] ='Account already exists ' .$payload->customerNo .' '.$payload->msisdn;
            }
        }
        catch(Exception $e){
            $err[]= $e->getMessage();
        }
        $data='';
        foreach($err as $e)
            $data .=$e.'';
        return ($data);
    }
    public static function updateAccount($payload) {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo)) {
            $err[]='accountNo may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }

        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function nameLookup($payload) {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo))  {
            $err[]='accountNo may not be empty nameLookup';
        }
        if (!isset($payload->msisdn) || empty($payload->msisdn)) {
            $err[]='msisdn may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
       /* if(checkTransid($payload)){
            $err[]='duplicate transaction';
        }
        */
        $data='';
        foreach($err as $e)
            $data .=$e.'';


        return ($data);
    }
    public static function requestCard($payload) {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            $err[]='accountNo or msisdn may not be empty ';
        }

        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }

        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function transactionLookup($payload){
        $err = array();

        if (!isset($payload->accountNo) || empty($payload->accountNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            $err[]='accountNo or msisdn may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
        if (!isset($payload->transref) || empty($payload->transref)) {
            $err[]='transRef may not be empty. transref is the transaction id you would like to lookup, where as transid is this current transaction';
        }
        if (!Validate::_checkTransid($payload->transref)) {
            $err[]='this transaction does not exist';
        }

        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function transferFunds($payload)    {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo)) {
            $err[]='accountNo may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
        if (!isset($payload->toAccountNo) || empty($payload->toAccountNo)) {
            $err[]='toAccountNo may not be empty';
        }
        if (!isset($payload->amount) || empty($payload->amount)) {
            $err[]='amount may not be empty';
        }
        if (!isset($payload->currency) || empty($payload->currency)) {
            $err[]='currency may not be empty';
        }



        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function enquiry($payload) {
        $err = array();
        if (!isset($payload->customerNo) || empty($payload->customerNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            $err[]='customerNo or msisdn may not be empty ';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
       /* if(self::checkTransid($payload->transid)){
            $err[]='duplicate transaction';
        }
        */
        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function accountState($payload) {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            $err[]='accountNo or msisdn may not be empty';
        }
        if (!isset($payload->statustxt) || empty($payload->statustxt)) {
            $err[]='status may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
        /*if(self::checkTransid($payload->transid)){
            $err[]='duplicate transaction';
        }
        */
        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function reserveAccount($payload) {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            return $err[]='accountNo or msisdn may not be empty';
        }

        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
        if (!isset($payload->amount) || empty($payload->amount)) {
            $err[]='amount may not be empty';
        }
        $data = isset($err) ? $err :false;

        return ($data);

    }
    public static function unReserveAccount($payload) {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo)) {
            return $err[]='accountNo may not be empty';
        }
        if (!isset($payload->msisdn) || empty($payload->msisdn)) {
            return $err[]='msisdn may not be empty';
        }
        if (!isset($payload->reference) || empty($payload->reference)) {
            return $err[]='reference may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
        if (!isset($payload->amount) || empty($payload->amount)) {
            $err[]='amount may not be empty';
        }

        if(isset($payload->accountNo) && Validate::_getSuspense($payload->accountNo) == 0){
            $err[]='You do not have funds to release at this time';
        }
        /*check ref and msisdn reserveaccount*/
        $flag = Validate::_checkRef($payload);

        if(Validate::_checkRef($payload) != 0){
            $err[]='reference is invalid';
            //die(print_r($err));
        }

       $data = isset($err) ? $err :'';

        return ($data);
    }
    public static function payUtility($payload)    {
        $err = array();
        if (!isset($payload->customerNo) || empty($payload->customerNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            return $err[]='customerNo or msisdn may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }
        if (!isset($payload->utilitycode) || empty($payload->utilitycode)) {
            $err[]='utilitycode may not be empty';
        }
        if (!isset($payload->utilityref) || empty($payload->utilityref)) {
            $err[]='utilityref may not be empty';
        }
        if (!isset($payload->amount) || empty($payload->amount)) {
            $err[]='amount may not be empty';
        }
        if (!isset($payload->currency) || empty($payload->currency)) {
            $err[]='currency may not be empty';
        }



        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function cashin($payload)    {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            return $err[]='accountNo or msisdn may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }

        if (!isset($payload->amount) || empty($payload->amount)) {
            $err[]='amount may not be empty';
        }
        if (!isset($payload->currency) || empty($payload->currency)) {
            $err[]='currency may not be empty';
        }



        $data = isset($err) ? $err :false;

        return ($data);
    }
    public static function linkAccount($payload)    {
        $err = array();
        if (!isset($payload->accountNo) || empty($payload->accountNo))
            if(!isset($payload->msisdn) || empty($payload->msisdn)) {
            return $err[]='accountNo or msisdn may not be empty';
        }
        if (!isset($payload->transid) || empty($payload->transid)) {
            $err[]='transid may not be empty';
        }

        if (!isset($payload->bankname) || empty($payload->bankname)) {
            $err[]='bank name may not be empty';
        }
        if (!isset($payload->bankbranch) || empty($payload->bankbranch)) {
            $err[]='bank branch name may not be empty';
        }
        if (!isset($payload->bankaccountname) || empty($payload->bankaccountname)) {
            $err[]='bank account name may not be empty';
        }
        if (!isset($payload->bankaccountnumber) || empty($payload->bankaccountnumber)) {
            $err[]='bank account number may not be empty';
        }

        $data = isset($err) ? $err :false;

        return ($data);
    }




}
