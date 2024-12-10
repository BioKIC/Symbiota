<?php

use PHPUnit\Framework\TestCase;
// include_once('./classes/Manager.php');
$SERVER_ROOT = '/Users/mf/Sites/Symbiota';
include_once('./classes/TaxonomyEditorManager.php');


require_once 'bootstrap.php';

class SplitSciNameTest extends TestCase
{
    public function testSplitScinameFromOccArrEasy()
    {
        // $myClass = new MyClass();
        $testArr = [];
        $testArr['sciname'] = 'Acer rubrum Fisher newtest FRESH';
        $testArr['specificepithet'] = 'rubrum';
        $testArr['scientificnameauthorship'] = 'FISHER';
        $testArr['tradeName'] = 'FRESH';
        $testArr['cultivarEpithet'] = 'newtest';
        $taxonEditorObj = new TaxonomyEditorManager();
        $result = $taxonEditorObj->splitScinameFromOccArr($testArr);

        $expectedResult = [];
        $expectedResult['base'] = 'Acer rubrum';
		$expectedResult['cultivarEpithet'] = 'newtest';
		$expectedResult['tradeName'] = 'FRESH';
		$expectedResult['author'] = 'FISHER';
        $expectedResult['nonItal'] = '';

        // $result = $str1 . $str2;

        $this->assertEquals($expectedResult, $result);
    }
    public function testSplitScinameFromOccArrMissingFeaturesInOccArray()
    {
        // $myClass = new MyClass();
        $testArr = [];
        $testArr['sciname'] = 'Acer rubrum Fisher newtest FRESH';
        $testArr['specificepithet'] = 'rubrum';
        $testArr['scientificnameauthorship'] = 'FISHER';
        $taxonEditorObj = new TaxonomyEditorManager();
        $result = $taxonEditorObj->splitScinameFromOccArr($testArr);

        $expectedResult = [];
        $expectedResult['base'] = 'Acer rubrum';
		$expectedResult['cultivarEpithet'] = '';
		$expectedResult['tradeName'] = '';
		$expectedResult['author'] = 'FISHER';
        $expectedResult['nonItal'] = 'newtest FRESH';

        // $result = $str1 . $str2;

        $this->assertEquals($expectedResult, $result);
    }
    public function testSplitScinameFromOccArrWithSubsp()
    {
        // $myClass = new MyClass();
        $testArr = [];
        $testArr['sciname'] = 'Acer rubrum subsp. carolinianum';
        $testArr['specificepithet'] = 'rubrum';
        $testArr['scientificnameauthorship'] = '(Walter) W. Stone';
        // $testArr['tradeName'] = 'FRESH';
        // $testArr['cultivarEpithet'] = 'newtest';
        $taxonEditorObj = new TaxonomyEditorManager();
        $result = $taxonEditorObj->splitScinameFromOccArr($testArr);

        $expectedResult = [];
        $expectedResult['base'] = 'Acer rubrum';
		$expectedResult['cultivarEpithet'] = '';
		$expectedResult['tradeName'] = '';
		$expectedResult['author'] = '(Walter) W. Stone';
        $expectedResult['nonItal'] = 'subsp. carolinianum';

        // $result = $str1 . $str2;

        $this->assertEquals($expectedResult, $result);
    }
}