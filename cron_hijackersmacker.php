<?php

/* 
Some scraper sites are created to make money by using advertising programs. In such case, they are called Made for AdSense sites or MFA. This derogatory term refers to websites that have no redeeming value except to lure visitors to the website for the sole purpose of clicking on advertisements.[1]

Made for AdSense sites are considered sites that are spamming search engines and diluting the search results by providing surfers with less-than-satisfactory search results. The scraped content is considered redundant by the public to that which would be shown by the search engine under normal circumstances, had no MFA website been found in the listings.

by author:adc
*/
 
define('DB_NAME', 'seellleertools');
define('DB_USER', 'ro9ot4');
define('DB_PASSWORD', '$3ll3Rt00l$');
define('DB_HOST', 'sellertools.cf7vcozqxaqb.us-west-1.rds.amazonaws.com');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 

include_once(dirname(__FILE__).'/classes/class.proxy.php');
include_once(dirname(__FILE__).'/classes/simple_html_dom.php');
include_once(dirname(__FILE__).'/classes/class.scrape.php');

// Get the 19 proxies for each page
$proxy = new Proxy();
$json = $proxy->get_random(349);
$proxies = json_decode($json, true);
#var_dump($json); #die();
unset($proxy);
echo "<br>".'PROXIES: '.count($proxies)."<hr>";
 
// Loop to pages scrape and search asins and ranks
$scrape = new Scrape();
$page = 0;
$adc_log = 'Total Proxies' . count($proxies) . "\r\n";
$adc_log_cnt = 0;
foreach($proxies as $proxy) { $page++;
    if(!empty($proxy)) {
         
    $sql = "SELECT u_id, asin FROM amz_products ";
    $sql .= " UNION ALL Select '0423' as u_id, 'B002KRDGC0' as asin";
    #$sql .= "  WHERE asin = 'B00KY5S81O' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $data = array();
        while ($rec = $result->fetch_assoc())
        {
            $sql = "";
            $u_id = $rec["u_id"];
            $asin = $rec["asin"];
            $adc_log .= "U_ID : ".$u_id . " | ASIN : ".$asin ."\r\n";
            #print_r("<brnProcessing user_id => ".$u_id . " ASIN => ".$asin . "<br>"); 
            #$asin = "B002KRDGC0";
            #$url = 'http://www.amazon.com/gp/offer-listing/B002KRDGC0/ref=dp_olp_0?ie=UTF8&condition=new&overridePriceSuppression=1';
            $url = 'http://www.amazon.com/gp/offer-listing/'.$asin.'/ref=dp_olp_0?ie=UTF8&condition=new&overridePriceSuppression=1';
            #echo $url . "<br>" ;
            #$proxy = "50.31.104.23:8800";
            #echo $proxy . "<br>" ;
            $html = $scrape->get_html($proxy, $url);
            $c = 0;
            #var_dump($html);echo"\n";
                $dom = new DOMDocument();
                libxml_use_internal_errors(TRUE); //disable libxml errors
                if(!empty($html))
                { //if any html is actually returned
                  
                    $dom->loadHTML($html);
                    libxml_clear_errors(); //remove errors for yucky html
                    
                    $a = array();$b = array();
                    $xpath = new DOMXPath($dom);
                    $hrefs = $xpath->evaluate("/html/body//a");
                    $hrefs = $xpath->evaluate('/html/body//div[@class="a-row a-spacing-mini olpOffer"]');
                    for ($i = 0; $i < $hrefs->length; $i++) {
                        $href = $hrefs->item($i);
                        $str = trim($href->textContent);
                        $a[] = explode("\n",$str);
                       
                        foreach ($a[$i] as $aa)
                        {
                            if (trim($aa) <> '')
                            {    
                                $b[$i][] = trim($aa);
                            }    
                        }
     
                        
                    }
                    #var_dump($b);die();
                    #echo "\n\n-------------------------------------------------------------------------------\n\n";
                 
                    $dom_xpath = new DOMXPath($dom);
                    $dom_row = $dom_xpath->query('//span[@class="a-size-medium a-text-bold"]/a');
                    $sql = "";
                    if($dom_row->length > 0){
                      foreach($dom_row as $row){
                            #echo $row->nodeValue."\n";
                            #echo $row->getAttribute('href')."\n";
                            $name = trim($row->nodeValue);
                                for($i = 0; $i<count($b); $i++)
                                { 
                                    if (in_array($name,$b[$i]) && $name <> '')
                                    {
                                        $data[$c]["other_seller"] = $name;
                                        $data[$c]["other_seller_url"] = $row->getAttribute('href');
                                        $data[$c]["price"] = $b[$i][0];
                                        $data[$c]["shipping"] = trim(str_replace("Shipping","",str_replace("&","",str_replace("+"," ",trim($b[$i][1]))))); 
                                        $data[$c]["shipping2"] = trim(str_replace("Shipping","",str_replace("&","",str_replace("+"," ",trim($b[$i][2]))))); 
                                        $data[$c]["shipping3"] = trim(str_replace("Shipping","",str_replace("&","",str_replace("+"," ",trim($b[$i][3]))))); 
 
                                        $data[$c]["u_id"] = $u_id; 
                                        $data[$c]["asin"] = $asin; 
                                        
                                        $shipping = '';
                                        if (($data[$c]["shipping"] <> '') && (trim(substr($data[$c]["shipping"],0,1)) <> '(')) $shipping = $data[$c]["shipping"];
                                        if (($data[$c]["shipping2"] <> '') && ($shipping == '') && (trim(substr($data[$c]["shipping2"],0,1)) <> '(')) $shipping = $data[$c]["shipping2"];
                                        if (($data[$c]["shipping3"] <> '') && ($shipping == '') && (trim(substr($data[$c]["shipping3"],0,1)) <> '(')) $shipping = $data[$c]["shipping3"];
                                        
                                        $sql = "INSERT ignore INTO amz_hijacker (u_id, asin, datetime, other_seller, url, price, shipping, date)
                                                VALUES ('".$u_id."', '".$asin."', '".date("Y-m-d h:i:s")."', '".$data[$c]["other_seller"]."', '".$data[$c]["other_seller_url"]."', '".$data[$c]["price"]."', '".$shipping."', '".date("Y-m-d")."')";
                                        
                                        if ($conn->query($sql) === TRUE) {
                                            //$log[] = "*";
                                            echo $sql . "\n\n" ;
                                            $adc_log_cnt++; 
                                        } else {
                                           echo "\n\nERROR -------> ".$sql . "\n\n" ;
                                            //$log[] = "~";//failed
                                        }
                                          
                                    }
                                }
                            $c++;
                            }
                        }
                  
                    if ($sql <> '')    $adc_log .= "sql : ".$sql . " | reference : ".$url ."\r\n";                

                }
            } // end while
         } //end of amz products loop
        
        #echo "\n\n\n";
        #echo count($data); 
        #var_dump($data); 
        $adc_log .= "Total Count : ".$adc_log_cnt ."\r\n";    
        $adc_log .= "\r\n";
        
        $fname = "hijack_".date("Ymdhis");
        $path = "/var/www/html/logs/".$fname.".log";
		file_put_contents($path, $adc_log);
        
        
        
        die();             
  
    }
   die();
}

$conn->close();
unset($conn);

die();

?>
