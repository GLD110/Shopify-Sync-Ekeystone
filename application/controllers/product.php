<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Product extends MY_Controller {

  public function __construct() {
    parent::__construct();
    $this->load->model( 'Product_model' );
    $this->load->model( 'Sku_model' );
    $this->load->model( 'Shopify_model' );

    // Define the search values
    $this->_searchConf  = array(
      'name' => '',
      'sku' => '',
      'shop' => $this->_default_store,
      'page_size' => $this->config->item('PAGE_SIZE'),
      'sort_field' => 'product_id',
      'sort_direction' => 'DESC',
    );
    $this->_searchSession = 'product_app_page';
  }

  public function index(){
    $this->is_logged_in();
    $this->manage();
  }

  public function manage( $page =  0 ){
    // Check the login
    $this->is_logged_in();

    // Init the search value
    $this->initSearchValue();

    // Get data
    $this->Product_model->rewriteParam($this->_searchVal['shop']);
    $arrCondition =  array(
      'name' => $this->_searchVal['name'],
      'sku' => $this->_searchVal['sku'],
      'sort' => $this->_searchVal['sort_field'] . ' ' . $this->_searchVal['sort_direction'],
      'page_number' => $page,
      'page_size' => $this->_searchVal['page_size'],
    );
    $data['query'] =  $this->Product_model->getList( $arrCondition );
    $data['total_count'] = $this->Product_model->getTotalCount();
    $data['page'] = $page;

    // Store List
    $arr = array();
    foreach( $this->_arrStoreList as $shop => $row ) $arr[ $shop ] = $shop;
    $data['arrStoreList'] = $arr;

    // Define the rendering data
    $data = $data + $this->setRenderData();

    // Load Pagenation
    $this->load->library('pagination');

    $this->load->view('view_header');
    $this->load->view('view_product', $data );
    $this->load->view('view_footer');
  }

  public function update( $type, $pk )
  {
    $data = array();

    switch( $type )
    {
        case 'type' : $data['type'] = $this->input->post('value'); break;
        case 'title' : $data['title'] = $this->input->post('value'); break;
        case 'sku' : $data['sku'] = $this->input->post('value'); break;
        case 'item_per_square' : $data['item_per_square'] = str_replace( ',', '.', $this->input->post('value') ); break;
    }
    $this->Product_model->update( $pk, $data );
  }

  Public function GetInventoryQuantityUpdates()
  {
    $sdk_key = $this->config->item('EKEYSTONE_SDK_KEY');
    $user_num = $this->config->item('FULL_ACCOUNT_NUM');

    $url = "http://order.ekeystone.com/wselectronicorder/electronicorder.asmx";
     $soap_request = '<?xml version="1.0" encoding="utf-8"?>
     <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
     <soap:Body>
     <GetInventoryQuantityUpdates xmlns="http://eKeystone.com">
       <Key>' . $sdk_key . '</Key>
       <FullAccountNo>' . $user_num . '</FullAccountNo>
     </GetInventoryQuantityUpdates>
     </soap:Body>
     </soap:Envelope>';

     $header = array(
         "POST /wselectronicorder/electronicorder.asmx HTTP/1.1",
         "Host: order.ekeystone.com",
         "Content-type: text/xml;charset=\"utf-8\"",
         "Accept: text/xml",
         "Cache-Control: no-cache",
         "Pragma: no-cache",
         "SOAPAction: \"http://eKeystone.com/GetInventoryQuantityUpdates\"",
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

  public function sync( $shop = '', $page = 1 )
  {
    $this->load->model( 'Process_model' );
    $this->load->model( 'Log_model' );
    if(empty($shop))
	    $shop = $this->_default_store;

    // Set the store information
    $this->Product_model->rewriteParam( $shop );

    $this->load->model( 'Shopify_model' );
    $this->Shopify_model->setStore( $shop, $this->_arrStoreList[$shop]->app_id, $this->_arrStoreList[$shop]->app_secret );

    // Get the lastest day
    $last_day = $this->Product_model->getLastUpdateDate();
    //$last_day = str_replace(' ', 'T', $last_day);

    // Retrive Data from Shop
    $count = 0;

    // Make the action with update date or page
    $action = 'products.json?';
    if( $last_day != '' && $last_day != $this->config->item('CONST_EMPTY_DATE') && $page == 1 )
    {
      $action .= 'limit=250&updated_at_min=' . urlencode( $last_day );
    }
    else
    {
      $action .= 'limit=20&page=' . $page;
    }

    // Retrive Data from Shop
    $productInfo = $this->Shopify_model->accessAPI( $action );

    //var_dump($productInfo);exit;

    // Store to database
    if( isset($productInfo->products) && is_array($productInfo->products) )
    {
      foreach( $productInfo->products as $product )
      {
        $this->Process_model->product_create( $product, $this->_arrStoreList[$shop] );
      }
    }

    // Get the count of product
    if( $last_day != '' && $last_day != $this->config->item('CONST_EMPTY_DATE') && $page == 1 )
    {
      $count = 0;
    }
    else
    {
      if( isset( $productInfo->products )) $count = count( $productInfo->products );
      $page ++;
    }

    $this->Log_model->add('CronJob', 'Product Sync', $last_day, $shop);

    if( $count == 0 )
      echo 'success';
    else
      echo $page . '_' . $count;
  }

  function manageSku(){
      // Check the login
      $this->is_logged_in();

      if($this->session->userdata('role') == 'admin'){
          $data['query'] =  $this->Sku_model->getList();
          $data['arrStoreList'] =  $this->_arrStoreList;

          $this->load->view('view_header');
          $this->load->view('view_sku', $data);
          $this->load->view('view_footer');
      }
  }

  function delSku(){
      if($this->session->userdata('role') == 'admin'){
          $id = $this->input->get_post('del_id');
          $returnDelete = $this->Sku_model->delete( $id );
          if( $returnDelete === true ){
              $this->session->set_flashdata('falsh', '<p class="alert alert-success">One item deleted successfully</p>');
          }
          else{
              $this->session->set_flashdata('falsh', '<p class="alert alert-danger">Sorry! deleted unsuccessfully : ' . $returnDelete . '</p>');
          }
      }
      else{
          $this->session->set_flashdata('falsh', '<p class="alert alert-danger">Sorry! You have no rights to deltete</p>');
      }
      redirect('product/manageSku');
      exit;
  }

  function createSku(){
     if($this->session->userdata('role') == 'admin'){
      $this->form_validation->set_rules('prefix', 'Prefix', 'callback_prefix_check');
      //$this->form_validation->set_rules('password', 'Password', 'required|matches[cpassword]');

      if ($this->form_validation->run() == FALSE){
          echo validation_errors('<div class="alert alert-danger">', '</div>');
          exit;
      }
      else{
            if($this->Sku_model->createSku()){
                echo '<div class="alert alert-success">This sku created successfully</div>';
                //redirect('product/manageSku');
                exit;
            }
            else{
                echo '<div class="alert alert-danger">Sorry ! something went wrong </div>';
                exit;
            }
          }
     }
     else{
         echo '<div class="alert alert-danger">Invalid sku</div>';
         exit;
     }
  }

  function updateSku( $key ){
    if($this->session->userdata('role') == 'admin'){
      $val = $this->input->post('value');
      if( $key == 'prefix' )
      $prefix =  $this->input->post('prefix');
      $data = array(
        $key => $val
      );

      $this->Sku_model->update( $prefix, $data );
    }
  }
}
