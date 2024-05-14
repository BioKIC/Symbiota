<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/ImageLibraryManager.php');
include_once($SERVER_ROOT . '/classes/ImageExplorer.php');
@header("Content-Type: text/html; charset=".$CHARSET);

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$formSubmit = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:"";

$imgManager = new ImageLibraryManager();
$imageExplorer = new ImageExplorer();

?>
<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/underscore-1.4.3.js"></script>
<script type="text/javascript" src="../js/backbone-0.9.10.js"></script>
<script type="text/javascript" src="../js/symb/imagelib.imgexplorer.js"></script>

<script src="../js/visualsearch.js" type="text/javascript"></script>
<!--[if (!IE)|(gte IE 8)]><!-->
<link href="../css/visualsearch-datauri.css" media="screen" rel="stylesheet" type="text/css"/>
<!--<![endif]-->
<!--[if lte IE 7]><!-->
<link href="../css/visualsearch.css" media="screen" rel="stylesheet" type="text/css"/>
<!--<![endif]-->

<style>
    .VS-interface.ui-autocomplete li {
        width: 230em;
    }

    /* Start by setting display:none to make this hidden.
Then we position it in relation to the viewport window
with position:fixed. Width, height, top and left speak
speak for themselves. Background we set to 80% white with
our animation centered, and no-repeating */
    .modal {
        display:    none;
        position:   fixed;
        z-index:    1000;
        top:        0;
        left:       0;
        height:     100%;
        width:      100%;
        background: rgba( 255, 255, 255, .8 )
        url('http://sampsonresume.com/labs/pIkfp.gif')
        50% 50%
        no-repeat;
    }

    /* When the body has the loading class, we turn
       the scrollbar off with overflow:hidden */
    body.loading {
        overflow: hidden;
    }

    /* Anytime the body has the loading class, our
       modal element will be visible */
    body.loading .modal {
        display: block;
    }
</style>
<div style="margin:10px;">
    <script type="text/javascript">
        <?php
            $pList = array();
            foreach($imgManager->getPhotographerList() as $uid => $pArr){
                $pList[] = (object)array(
                    'value' => (string)$uid,
                    'label' => $pArr['name']);
            }

            echo("var photographers = ".json_encode($pList).";");
            echo("var collections = ".$imageExplorer->getCollections().";");
            echo("var countries = [");

            $countries = $imageExplorer->getCountries();

            for($i = 0; $i < count($countries); $i++) {
                echo('"'.$countries[$i].'"');
                if ($i < count($countries)-1) {
                    echo(', ');
                }
            }
            echo('];');

            echo("var states = [");

            $states = $imageExplorer->getStates();

            for($i = 0; $i < count($states); $i++) {
                echo('"'.$states[$i].'"');
                if ($i < count($states)-1) {
                    echo(', ');
                }
            }
            echo('];');
        ?>

        $(document).ready(function () {

            var taxaSuggest = function (searchTerm, callback) {
                if (searchTerm.length >= 3) {
                    $.get('rpc/gettaxasuggest.php?term=' + searchTerm, function (data, status) {
                        //callback(eval(data));
                    	callback(data);
                    });
                }
            };

            var options = {
                displayUrl : "rpc/displayimagesforid.php",
                limit: 50,
                start: 0,
                facets: [
                    {
                        name: 'taxa',
                        source: taxaSuggest
                    },
                    { name: 'photographer',
                        source: function (searchTerm, callback) {
                            callback(photographers);
                        }
                    },
                    { name: 'collection',
                        source: function (searchTerm, callback) {
                            callback(collections);
                        }
                    },
                    { name: 'country',
                        source: function (searchTerm, callback) {
                            callback(countries);
                        }
                    },
                    { name: 'state',
                        source: function (searchTerm, callback) {
                            callback(states);
                        }
                    },
                    { name: 'tags',
                        source: function (searchTerm, callback) {
                            callback(<?php echo $imageExplorer->getTags(); ?>);
                        }
                    }
                ],
                initialCriteria : {

                    <?php for($i = 0; $i < count($taxa); $i++) {
                       echo "taxa: '".$taxa."'";
                       if ($i < count($taxa)-1) {
                           echo ", ";
                       }
                    } ?>
                }
            };

            var imgExplorer = new ImageExplorer(options);

            imgExplorer.init("imagesearch");

            $('#nextPage').click(function(event) {
                imgExplorer.nextPage();
            });

            $('#previousPage').click(function(event) {
                imgExplorer.previousPage();
            });
        });
    </script>

    <div id="imagesearch"></div>
</div>