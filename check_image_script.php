<?php
use Magento\Framework\App\Bootstrap;
 
require __DIR__ . '/app/bootstrap.php';
 
$params = $_SERVER;
 
$bootstrap = Bootstrap::create(BP, $params);
 
$obj = $bootstrap->getObjectManager();
$objectManager = $obj;
 $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$tableName = $resource->getTableName('catalog_product_entity_media_gallery'); 
$tableName2 = $resource->getTableName('catalog_product_entity_media_gallery_value_to_entity'); 

$fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
$mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
$productRepository =  $objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface');


$mediaPath .= "catalog/product" ;

$password = $_GET['password'] ?? "";
if($password != "123" ) exit('password');

$limit = $_GET['limit'] ?? 100;
$image = $_GET['image'] ?? "";
$active = $_GET['active'] ?? 1;
$image_adjust = $_GET['image_adjust'] ?? "";

$sql2 = "SELECT entity_id FROM `mg_catalog_product_entity_int`
WHERE attribute_id = (
    SELECT attribute_id FROM `mg_eav_attribute`
    WHERE `attribute_code` LIKE 'status'
) AND `mg_catalog_product_entity_int`.value = $active";
$sql = "Select * FROM " . $tableName." join  $tableName2 on $tableName.value_id=$tableName2.value_id where $tableName2.entity_id in ($sql2) limit $limit";
$result = $connection->fetchAll($sql);
echo "<pre>";
$i = 1;
function mycopy($s1,$s2) {
    $path = pathinfo($s2);
    if (!file_exists($path['dirname'])) {
        mkdir($path['dirname'], 0777, true);
    }   
    if (file_exists($s2)) {
        //unlink($s2);    
    }
    
   	if (!@file_put_contents($s2,  @file_get_contents($s1))) {
        //$this->fileSystemInputOutput->cp($s1, $s2)
        //@file_put_contents($s1, $s2);
        $errors= error_get_last();
       	print_r($errors);die;
    }
}
foreach ($result as $key => $value) {
	$imagePath = $mediaPath.$value['value'];
	if($image_adjust && $image){
		if( $image && !file_exists($imagePath) && strripos($value['value'], $image_adjust) !== false){
			$origImage = __DIR__."/Imagesbackup/$image";
			echo "\nnot copied $origImage";
			mycopy($origImage,$imagePath) ;
			print_r( "\n $i) Entity_id: ". $value['entity_id']."  Image Path : ". $imagePath);
			$i++;
		}
	}
	else if($image ){
		if( $image && !file_exists($imagePath) && strripos($value['value'], $image) !== false){
			$origImage = __DIR__."/Imagesbackup/$image";
			echo "\nnot copied $origImage";
			mycopy($origImage,$imagePath) ;
			print_r( "\n $i) Entity_id: ". $value['entity_id']."  Image Path : ". $imagePath);
			$i++;
		}
	}
	else{
		if( !file_exists($imagePath) ){
			//$productUrl = $productRepository->getById($value['entity_id'])->getProductUrl();

			print_r( "\n $i) Entity_id: ". $value['entity_id']."  Image Path : ". $imagePath);	
			//echo "\n $productUrl";
			$i++;
		}
	}
	
	
}
?>