<?php
ini_set('display_errors', 1);
use Magento\Framework\App\Bootstrap;
 
require __DIR__ . '/app/bootstrap.php';
 
$params = $_SERVER;
 
$bootstrap = Bootstrap::create(BP, $params);
 
$obj = $bootstrap->getObjectManager();
 
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');
$objectManager = $obj;
$categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
$categoryColFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory'); 
$collection = $categoryColFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('url_path');
$categories = [];
$categoryCache = [];

//echo count($collection);
foreach ($collection as $category) {
    
        $structure = explode('/', $category->getPath());
        $pathSize = count($structure);
        $categoriesCache[$category->getId()] = $category;
		if ($pathSize > 1) {
            $path = [];
            for ($i = 1; $i < $pathSize; $i++) {
                $name = trim($collection->getItemById((int)$structure[$i])->getName());
                $path[] = quoteDelimiter($name);
            }
            /** @var string $index */
            $index = standardizeString(
                implode('/', $path)
            );
            $categories[$index] = $category->getId();
		}
}
function standardizeString($string)
{
    return mb_strtolower($string);
}
function quoteDelimiter($string)
{
    return str_replace('/', '\\' . '/', $string);
}
function upsertCategory($categoryPath)
{
    global $categories; 
    $categoryPaths = explode('/',$categoryPath);
    $categoryPaths = array_map('trim', $categoryPaths);
    $categoryPaths = implode('/',$categoryPaths);
    /** @var string $index */
    $index = standardizeString($categoryPaths);

    if (isset($categories[$index])) {
         return $categories[$index];
    }else{
        //return false;
        echo $categoryPath;
        $pathParts = preg_split('~(?<!\\\)' . preg_quote('/', '~') . '~', $categoryPath);
        $parentId = 1;
        $path = '';

        foreach ($pathParts as $pathPart) {
            $path .= standardizeString($pathPart);
            if (!isset($categories[$path]) && $pathPart) {
                $categories[$path] = createCategory($pathPart, $parentId);
            }
            if(isset($categories[$path])){
                $parentId = $categories[$path];
                $path .= '/';    
            }
            
        }
    }

    return false;
}
function unquoteDelimiter($string)
{
    return str_replace('\\' .'/', '/', $string);
}
function createCategory($name, $parentId)
{
    global $categories; 
    global $categoriesCache; 
    global $categoryFactory;

    
    $category = $categoryFactory->create();
    if (!($parentCategory = getCategoryById($parentId))) {
        $parentCategory = $categoryFactory->create()->load($parentId);
    }
    $category->setPath($parentCategory->getPath());
    $category->setParentId($parentId);
    $category->setName(unquoteDelimiter($name));
    $category->setIsActive(true);
    $category->setIncludeInMenu(true);
    $category->setAttributeSetId($category->getDefaultAttributeSetId());
    try{
        $category->save();
    }catch(\Exception $e){
        echo $e->getMessage();
        echo  unquoteDelimiter($name);
        echo $parentId;
        die;
    }
    
    $categoriesCache[$category->getId()] = $category;
    return $category->getId();
}
 function getCategoryById($categoryId)
{
    global $categoriesCache; 
    return $categoriesCache[$categoryId] ?? null;
}
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$catalog_product_entityTableName = $resource->getTableName('catalog_product_entity'); 
$catalog_category_productTableName = $resource->getTableName('catalog_category_product'); 


$csv = array();
$file = fopen('testsheet.csv', 'r');
$csvCategories = [];
$ids = [];
$skus = [];
while (($result = fgetcsv($file)) !== false)
{
    if(isset($result[8])){
        $sepCategories = explode(',', $result[8]);
        $csvCategories = array_merge($csvCategories,$sepCategories);
    }
    $csv[] = $result;
    $sku = $result[0];
    $productId = $connection->fetchOne("SELECT `entity_id` FROM `$catalog_product_entityTableName` WHERE `sku` = '$sku'");
    if(!$productId) {
        $skus[] = $sku;
    }else{
        $ids[] = $productId;
    }
    /*if($productId){
         if(isset($result[8])){
             $sepCategories = explode(',', $result[8]);
             foreach ($sepCategories as $category) {
                $csvCategory = $category;
                $catId = upsertCategory($csvCategory,$categories);
                if($catId){
                    $isExist = $connection->fetchOne("SELECT `entity_id` FROM `$catalog_category_productTableName` WHERE `category_id` = '$catId' AND `product_id` = '$productId'");
                    if(!$isExist){

                        echo "inserted CatId: $catId |||  ProdId: $productId   |||   Sku: $sku in |||  Cat: $csvCategory <br>";
                        $data = [
                                    'category_id' => $catId,
                                    'product_id' => $productId,
                                    
                                ];
                        $connection->insert($catalog_category_productTableName, $data);

                    }
                }
            }
        }
    }*/
    
}
fclose($file);
echo "<pre>";
echo count($ids);
print_r($ids);
print_r($skus);
die;


/*$csvCategories = array_unique($csvCategories);
foreach ($csvCategories as $csvCategory) {
	$catId = upsertCategory($csvCategory);
	if(!$catId){
		echo "CatId:  $catId Category : $csvCategory <br>\n";	
	}
	
}*/
 
?>