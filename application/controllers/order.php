<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Order extends MY_Controller {

  public function __construct() {
    parent::__construct();
    $this->load->model( 'Order_model' );

    // Define the search values
    $this->_searchConf  = array(
      'customer_name' => '',
      'order_name' => '',
      'shop' => $this->_default_store,
      'page_size' => $this->config->item('PAGE_SIZE'),
      'created_at' => '',
      'sort_field' => 'created_at',
      'sort_direction' => 'DESC',
    );

    $this->_searchSession = 'order_sels';
  }

  private function _checkDispatchCode( $code1, $code2 )
  {
    // if the first code is empty or both are same, return code2
    if( $code1 == '' || $code1 == $code2 ) return $code2;

    // If the second code is empty, return code1
    if( $code2 == '' ) return $code1;

    $arrRule = array( 'HH', 'YH', 'GM', 'SU', 'SF', 'FR', 'AP', 'JM', 'AO', 'AJ', 'NO' );

    $pos1 = array_search( $code1, $arrRule );
    $pos2 = array_search( $code2, $arrRule );

    if( $pos2 !== false && $pos1 < $pos2 ) return $code1;

    return $code2;
  }

  public function index(){
      $this->is_logged_in();

      $this->manage();
  }

  public function manage( $page =  0 ){

    $this->_searchVal['shop'] = trim( $this->_searchVal['shop'], 'http://' );
    $this->_searchVal['shop'] = trim( $this->_searchVal['shop'], 'https://' );

    // Check the login
    $this->is_logged_in();

    // Init the search value
    $this->initSearchValue();

    $created_at = $this->_searchVal['created_at'];
    if($created_at == '')
    {
        $this->_searchVal['created_at'] = date('m/d/Y');
    }
    // Get data
    $arrCondition =  array(
       'customer_name' => $this->_searchVal['customer_name'],
       'order_name' => $this->_searchVal['order_name'],
       'page_number' => $page,
       'page_size' => $this->_searchVal['page_size'],
       'created_at' => $this->_searchVal['created_at'],
       'sort' => $this->_searchVal['sort_field'] . ' ' . $this->_searchVal['sort_direction'],
    );

    $this->Order_model->rewriteParam($this->_default_store);
    $data['query'] =  $this->Order_model->getList( $arrCondition );
    $data['total_count'] = sizeof($data['query']->result());//$this->Order_model->getTotalCount();
    $data['page'] = $page;

      //var_dump($data['query']);exit;

    // Define the rendering data
    $data = $data + $this->setRenderData();

    // Store List
    $arr = array();
    foreach( $this->_arrStoreList as $shop => $row ) $arr[ $shop ] = $shop;
    $data['arrStoreList'] = $arr;

    // Rate
    //$data['sel_rate'] = $this->_arrStoreList[ $this->_searchVal['shop'] ]->rate;

    // Load Pagenation
    $this->load->library('pagination');

    // Renter to view
    $this->load->view('view_header');
    $this->load->view('view_order', $data );
    $this->load->view('view_footer');
  }

  public function sync( $shop = '' )
  {
    $this->load->model( 'Process_model' );
    $this->load->model( 'Log_model' );

    if(empty($shop))
        $shop = $this->_default_store;

    $this->load->model( 'Shopify_model' );
    $this->Shopify_model->setStore( $shop, $this->_arrStoreList[$shop]->app_id, $this->_arrStoreList[$shop]->app_secret );

    // Get the lastest day
    $this->Order_model->rewriteParam( $shop );
    $last_day = $this->Order_model->getLastOrderDate();

    $last_day = str_replace(' ', 'T', $last_day);
    //$last_day = '2017-08-04T00:00:00-05:00';

    //var_dump($last_day);exit;

    $param = 'status=any&limit=250';
    if( $last_day != '' ) $param .= '&updated_at_min=' . $last_day ;
    $action = 'orders.json?' . $param;

    // Retrive Data from Shop
    $orderInfo = $this->Shopify_model->accessAPI( $action );

    //var_dump(    $orderInfo  );exit;

    if($orderInfo != null){
        foreach( $orderInfo->orders as $order )
        {
          $this->Process_model->order_create( $order, $this->_arrStoreList[$shop] );
        }
    }

    $this->Log_model->add('CronJob', 'Order Sync', $last_day, $shop);
    echo 'success';
  }

  Public function ShipOrderDropShip( $sdk_key = '', $user_num = '',
    $FullPartNo, $Quant,
    $DropShipFirstName, $DropShipMiddleInitial, $DropShipLastName,
    $DropShipCompany,
    $DropShipAddress1, $DropShipAddress2, $DropShipCity, $DropShipState, $DropShipPostalCode,
    $DropShipPhone, $ropShipCountry, $DropShipEmail, $PONumber,
    $AdditionalInfo, $ServiceLevel )
  {
    $sdk_key = $this->config->item('EKEYSTONE_SDK_KEY');
    $user_num = $this->config->item('FULL_ACCOUNT_NUM');

    $url = "http://order.ekeystone.com/wselectronicorder/electronicorder.asmx";
     $soap_request = '<?xml version="1.0" encoding="utf-8"?>
     <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
     <soap:Body>
      <ShipOrderDropShip xmlns="http://eKeystone.com">
        <Key>' . $sdk_key . '</Key>
        <FullAccountNo>' . $user_num . '</FullAccountNo>
        <FullPartNo>string</FullPartNo>
        <Quant>string</Quant>
        <DropShipFirstName>string</DropShipFirstName>
        <DropShipMiddleInitial>string</DropShipMiddleInitial>
        <DropShipLastName>string</DropShipLastName>
        <DropShipCompany>string</DropShipCompany>
        <DropShipAddress1>string</DropShipAddress1>
        <DropShipAddress2>string</DropShipAddress2>
        <DropShipCity>string</DropShipCity>
        <DropShipState>string</DropShipState>
        <DropShipPostalCode>string</DropShipPostalCode>
        <DropShipPhone>string</DropShipPhone>
        <DropShipCountry>string</DropShipCountry>
        <DropShipEmail>string</DropShipEmail>
        <PONumber>string</PONumber>
        <AdditionalInfo>string</AdditionalInfo>
        <ServiceLevel>string</ServiceLevel>
      </ShipOrderDropShip>
     </soap:Body>
     </soap:Envelope>';

     $header = array(
         "POST /wselectronicorder/electronicorder.asmx HTTP/1.1",
         "Host: order.ekeystone.com",
         "Content-type: text/xml;charset=\"utf-8\"",
         "Accept: text/xml",
         "Cache-Control: no-cache",
         "Pragma: no-cache",
         "SOAPAction: \"http://eKeystone.com/ShipOrderDropShip\"",
         "Content-length: ".strlen($soap_request),
     );

     //var_dump(123456789);exit;

     $soap_do = curl_init();
     curl_setopt($soap_do, CURLOPT_URL,            $url );
     curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
     curl_setopt($soap_do, CURLOPT_POST,           true );
     curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
     curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);
     $result = curl_exec($soap_do);

     /*$result = '<?xml version="1.0" encoding="UTF-8"?>
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                   <soap:Body>
                      <GetInventoryQuantityUpdatesResponse xmlns="http://eKeystone.com">
                         <GetInventoryQuantityUpdatesResult>
                            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns="" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" id="InventoryUpdates">
                               <xs:element name="InventoryUpdates" msdata:IsDataSet="true" msdata:UseCurrentLocale="true">
                                  <xs:complexType>
                                     <xs:choice minOccurs="0" maxOccurs="unbounded">
                                        <xs:element name="Table">
                                           <xs:complexType>
                                              <xs:sequence>
                                                <xs:element name="VCPN" type="xs:string" />
                                                <xs:element name="vencode" type="xs:string" minOccurs="0" />
                                                <xs:element name="partnumber" type="xs:string" minOccurs="0" />
                                                <xs:element name="totalqty" type="xs:int" minOccurs="0" />
                                                <xs:element name="SOInv" type="xs:string" minOccurs="0" />
                                                <xs:element name="MinToSell" type="xs:int" minOccurs="0" />
                                                <xs:element name="ShippingFlag" type="xs:boolean" minOccurs="0" />
                                                <xs:element name="CoreCharge" type="xs:decimal" minOccurs="0" />
                                              </xs:sequence>
                                              <xs:sequence>
                                                 <xs:element name="VCPN" type="xs:string">VCPN</xs:element>
                                                 <xs:element name="vencode" type="xs:string" minOccurs="0">vencode</xs:element>
                                                 <xs:element name="partnumber" type="xs:string" minOccurs="0">ZON7103_GMC2500</xs:element>
                                                 <xs:element name="totalqty" type="xs:int" minOccurs="0">2</xs:element>
                                                 <xs:element name="SOInv" type="xs:string" minOccurs="0">SOInv</xs:element>
                                                 <xs:element name="MinToSell" type="xs:int" minOccurs="0">MinToSell</xs:element>
                                                 <xs:element name="ShippingFlag" type="xs:boolean" minOccurs="0">ShippingFlag</xs:element>
                                                 <xs:element name="CoreCharge" type="xs:decimal" minOccurs="0">CoreCharge</xs:element>
                                              </xs:sequence>
                                              <xs:sequence>
                                                 <xs:element name="VCPN" type="xs:string">VCPN</xs:element>
                                                 <xs:element name="vencode" type="xs:string" minOccurs="0">vencode</xs:element>
                                                 <xs:element name="partnumber" type="xs:string" minOccurs="0">ZON3103GMC2500</xs:element>
                                                 <xs:element name="totalqty" type="xs:int" minOccurs="0">2</xs:element>
                                                 <xs:element name="SOInv" type="xs:string" minOccurs="0">SOInv</xs:element>
                                                 <xs:element name="MinToSell" type="xs:int" minOccurs="0">MinToSell</xs:element>
                                                 <xs:element name="ShippingFlag" type="xs:boolean" minOccurs="0">ShippingFlag</xs:element>
                                                 <xs:element name="CoreCharge" type="xs:decimal" minOccurs="0">CoreCharge</xs:element>
                                              </xs:sequence>
                                              <xs:sequence>
                                                 <xs:element name="VCPN" type="xs:string">VCPN1</xs:element>
                                                 <xs:element name="vencode" type="xs:string" minOccurs="0">vencode</xs:element>
                                                 <xs:element name="partnumber" type="xs:string" minOccurs="0">ZON7103_GMC3500</xs:element>
                                                 <xs:element name="totalqty" type="xs:int" minOccurs="0">2</xs:element>
                                                 <xs:element name="SOInv" type="xs:string" minOccurs="0">SOInv</xs:element>
                                                 <xs:element name="MinToSell" type="xs:int" minOccurs="0">MinToSell</xs:element>
                                                 <xs:element name="ShippingFlag" type="xs:boolean" minOccurs="0">ShippingFlag</xs:element>
                                                 <xs:element name="CoreCharge" type="xs:decimal" minOccurs="0">CoreCharge</xs:element>
                                              </xs:sequence>
                                           </xs:complexType>
                                        </xs:element>
                                     </xs:choice>
                                  </xs:complexType>
                                  <xs:unique name="Constraint1" msdata:PrimaryKey="true">
                                     <xs:selector xpath=".//Table" />
                                     <xs:field xpath="VCPN" />
                                  </xs:unique>
                               </xs:element>
                            </xs:schema>
                            <diffgr:diffgram xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" />
                         </GetInventoryQuantityUpdatesResult>
                      </GetInventoryQuantityUpdatesResponse>
                   </soap:Body>
                </soap:Envelope>';*/

     $xmlString = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result);
     $xml = SimpleXML_Load_String($xmlString);
     $xml = new SimpleXMLElement($xml->asXML());
     $array = $xml->soapBody->GetInventoryQuantityUpdatesResponse->GetInventoryQuantityUpdatesResult->xsschema->xselement->xscomplexType->xschoice->xselement->xscomplexType->xssequence;

     //var_dump($array);exit;

     if(empty($shop))
      $shop = $this->_default_store;

     // Set the store information
     $this->Product_model->rewriteParam( $shop );

     $this->load->model( 'Shopify_model' );
     $this->Shopify_model->setStore( $shop, $this->_arrStoreList[$shop]->app_id, $this->_arrStoreList[$shop]->app_secret );
     $action = 'products.json';

     foreach($array as $a)
     {
        if(is_numeric((string)$a->xselement[3]))
        {
          $partnumber = (string)$a->xselement[2];
          $totalqty = (string)$a->xselement[3];
          $variant_info = $this->Product_model->getVariantFromSku($partnumber);
          $product_id = $variant_info->product_id;
          $variant_id = $variant_info->variant_id;
          $products_array = array(
              'product' => array(
                  'id' => $product_id,
                  'variants' => array(
                    array(
                      "id" => $variant_id,
                      "inventory_quantity" => $totalqty,
                      "inventory_management" => 'shopify'
                    )
                  )
              )
          );

          // Retrive Data from Shop
          $action = 'products/' . $product_id . '.json';
          $productInfo = $this->Shopify_model->accessAPI( $action, $products_array, 'PUT' );
        }
     }

     $this->load->model( 'Log_model' );
     $this->Log_model->add('CronJob', 'GetInventoryQuantityUpdates', '---', $shop);

  }
}
