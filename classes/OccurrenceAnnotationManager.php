<?php

include_once("OccurrenceManager.php");

class OccurrenceAnnotationManager extends OccurrenceManager {

	/**
	 * Fetch a document describing the history of edits to occurrences
	 * in the current collection context as a set of annotations in an RDF/XML document.
	 *
	 * Each set of edits of an occurrence by a person at one time are grouped into
	 * a single annotation.
	 *
	 * @return RDF/XML document containing a list of zero or more annotations.
	 */
	public function getAnnotations() {
		$result = "";

		$serviceFound = false;

		// Construct the heading of the RDF/XML document
		if (!$serviceFound) {
			$result = fillAnnotationHeader(fetchUUID());
		}

		$join ="";
		$sqlWhere = "";
		// limit to current collection(s)
		if(array_key_exists("db",$this->searchTermArr)){
			$join = "left join omoccurrences o on e.occid = o.occid ";
			if(strpos($this->searchTermArr["db"],"all") === false){
				$dbStr = preg_replace('/;catid:\d*/','',$this->searchTermArr["db"]);
				$sqlWhere .= "AND (o.CollID IN(".str_replace(";",",",trim($dbStr,';')).")) ";
			}
			elseif(preg_match('/;catid:(\d+)/',$this->searchTermArr["db"],$matches)){
				$catId = $matches[1];
				if($catId) $sqlWhere .= "AND (o.CollID IN(SELECT collid FROM omcollcatlink WHERE (ccpk = ".$catId."))) ";
			}
		}

        // variables used in constructing an annotation.
		$uuid = "";
		$timestamp = "";
		$annotator = "";
		$emailsha1hash = "";
		$institutioncode = "";
		$collectioncode = "";
		$catalognumber = "";
		$occurrenceid = "";
		$kvpList = array();  // Note: array implementation allows for one value for each key - keys can't repeat within an annotation.

		// find sets of edits to create as annotations.  Get timestamp as metadata
		$sql = "select count(*), e.uid, e.initialtimestamp, e.occid " .
		       "  from omoccuredits e $join $sqlWhere ".
		       "  group by e.uid, e.initialtimestamp, e.occid, " .
		       "  order by e.uid, e.occid, e.initialtimestamp ";
		if ($statement = $connection->prepare($preparesql)) {
			$statement->execute();
			$statement->bind_result($uid, $timestamp, $occid);
			while ($statement->fetch()) {


				// fetch the annotator's name and md5 hash of email for the annotation metadata
				$sql = "select concat(firstname, ' ', lastname), sha1(email) from users where uid = ?";
				if ($statement_a = $connection->prepare($preparesql)) {
					$statement_a->bind_param("i", $uid);
					$statement_a->execute();
					$statement_a->bind_result($annotator, $emailsha1hash);
					$statement_a->fetch();
					$statement_a->close();
				}

				// fetch the information to build a query selector
				$sql = "select c.institutioncode, c.collectioncode, o.catalognumber " .
						       "  from omoccurrences o inner join omcollections c on o.collid = c.collid " .
						       "  where occid = ? ";
				if ($statement_a = $connection->prepare($preparesql)) {
					$statement_a->bind_param("i", $occid);
					$statement_a->execute();
					$statement_a->bind_result($institutioncode, $collectioncode, $catalognumber);
					$statement_a->fetch();
					$statement_a->close();
				}

				// fetch body of the annotation
				$sql = "select fieldname, fieldvaluenew  " .
						       "  from omoccuredits " .
						       "  where uid = ? and occid = ? and initialtimestamp = ? ";
				if ($statement_a = $connection->prepare($preparesql)) {
					$statement_a->bind_param("iis", $uid, $occid, $timestamp);
					$statement_a->execute();
					$statement_a->bind_result($key, $value);
					while ($statement_a->fetch()) {
						$kvpList[$key]=$value;
					}
					$statement_a->close();
				}

				// is service to build the annotation available?
				if ($serviceFound) {
					// build annotation with service.
				} else {
					// if not, fill in a template
					$result .= fillAnnotationTemplate(fetchUUID(), $timestamp, $annotator, $emailsha1hash, $institutioncode, $collectioncode, $catalognumber, $occurrenceid, $kvpList);
				}

				// reset the array of key value pairs
				$kvpList = array();

			}
			$statement->close();
		}

		// Finish up the RDF/XML document.
		if (!$serviceFound) {
			$result .= "</rdf:RDF>";
		}

		return $result;
	}

	/**
	 * Mint a UUID
	 *
	 * @return a newly minted UUID
	 *
	 */
	public function fetchUUID() {
		$result = null;
		// implemented using MySQL's uuid() function.
		$sql = "select uuid() ";
		if ($statement_a = $connection->prepare($preparesql)) {
			$statement_a->execute();
			$statement_a->bind_result($result);
			$statement_a->fetch();
			$statement_a->close();
		}
		return $result;
	}

	/**
	 * Obtain the header for an RDF/XML document using the
	 * oa, oad, dwc, dwcFP namespaces into which annotations
	 * can be placed.
	 *
	 * @param string $uuid, the uuid for the document.
	 * @returns a block of populated <rdf:RDF><rdf:Description></rdf:Description>
	 *    not including a closing </rdf:RDF> tag.
	 */
	public static function fillAnnotationHeader($uuid) {
		$result = "<rdf:RDF
	xmlns:dwcFP=\"http://filteredpush.org/ontologies/oa/dwcFP.owl#\"
	xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"
	xmlns:oad=\"http://filteredpush.org/ontologies/oa/oad.rdf#\"
	xmlns:foaf=\"http://xmlns.com/foaf/0.1/\"
	xmlns:owl=\"http://www.w3.org/2002/07/owl#\"
	xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
	xmlns:dwc=\"http://rs.tdwg.org/dwc/terms/\"
	xmlns:dct=\"http://purl.org/dc/term/\"
	xmlns:oa=\"http://www.w3.org/ns/oa#\"
	xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\"
	xmlns:cnt=\"http://www.w3.org/2011/content#\" >
	<rdf:Description rdf:about=\"urn:uuid:$uuid#\">
	    <rdf:type rdf:resource=\"http://www.w3.org/2002/07/owl#Ontology\"/>
	</rdf:Description>\n";
         return $result;
	}

	/**
	 * Construct an oa/oad/dwc/dwcFP annotation.
	 *
	 * Motivated by editing.
	 * Expectation: Update.
	 * Evidence: "Approved Edit in Symbiota."
	 *
	 * @param unknown_type $uuid for the annotation
	 * @param unknown_type $timestamp annotatedAt
	 * @param unknown_type $annotator foaf:name of annotatedBy
	 * @param unknown_type $emailsha1hash foaf:mbox_sha1sum of annotatedBy
	 * @param unknown_type $institutioncode KVPair selector
	 * @param unknown_type $collectioncode KVPair selector
	 * @param unknown_type $catalognumber KVPair selector
	 * @param unknown_type $occurrenceid KVPair selector
	 * @param unknown_type $kvpList array of dwc:$keys and $values
	 *
	 * @return RDF/XML for a single annotation, without surrounding header and footer.
	 */
	public static function fillAnnotationTemplate($uuid, $timestamp, $annotator, $emailsha1hash, $institutioncode, $collectioncode, $catalognumber, $occurrenceid, $kvpList)  {
		$id = "\$Id$";  // obtain svn:keyword properties?
		$rev = "\$Rev$";
$result = "         <oa:Annotation rdf:about=\"urn:uuid:$uuid#Annotation\">
		  <oa:motivatedBy rdf:resource=\"http://www.w3.org/ns/oa#editing\"/>
		  <oa:annotatedAt>$timestamp</oa:annotatedAt>
		  <oa:serializedBy rdf:resource=\"urn:uuid:$uuid#Serializer_0\"/>
		  <oa:annotatedBy>
		     <foaf:Agent rdf:about=\"urn:uuid:$uuid#Annotator_0\">
		        <foaf:name>$annotator</foaf:name>
		        <foaf:mbox_sha1sum>$emailsha1hash</foaf:mbox_sha1sum>
		      </foaf:Agent>
		  </oa:annotatedBy>
		  <oad:hasExpectation>
		     <oad:Expectation_Update />
		  </oad:hasExpectation>
		  <oa:hasTarget>
		     <oa:SpecificResource rdf:about=\"urn:uuid:$uuid#Target_0\">
		        <oa:hasSelector/>
		           <oad:KVPairQuerySelector rdf:about=\"urn:uuid:$uuid#Selector_0\>
		              <dwc:instututionCode>$institutioncode</dwc:institutionCode>
		              <dwc:collectionCode>$collectioncode</dwc:collectionCode>
		              <dwc:catalogNumber>$catalognumber</dwc:catalogNumber>
		              <dwcFP:hasOccurrenceID>$occurrenceid</dwcFP:hasOccurrenceID>
		              <rdf:type rdf:resource=\"http://filteredpush.org/ontologies/oa/dwcFP.owl#Occurrence\"/>
		           </oad:KVPairQuerySelector>
		        <oa:hasSource rdf:resource=\"dwcFP:AnySuchResource\"/>
		     </oa:SpecificResource>
		  </oa:hasTarget>
		  <oad:hasEvidence>
		     <oad:Evidence rdf:about=\"urn:uuid:$uuid#Evidence_0\">
		        <cnt:chars xml:lang=\"en\">Approved Edit in Symbiota.</cnt:chars>
		        <rdf:type rdf:resource=\"http://www.w3.org/2011/content#ContentAsText\"/>
		     </oad:Evidence>
		  </oad:hasEvidence>
		  <oa:serializedBy>
            <foaf:Agent rdf:about=\"http://sourceforge.net/p/symbiota/svn/$rev/tree/trunk/classes/OccurrenceAnnotationManager\">
                <foaf:name>$id</foaf:name>
            </foaf:Agent>
		  </oa:serialziedBy>
          <oa:hasBody>
              <oa:Body rdf:about=\"urn:uuid:$uuid#Body_0\">\n";
		foreach ($kvpList as $key => $value) {
		    $result .= "     		  <dwc:$key>$value</dwc:$key>\n";
		}
		$result .= "             </oa:Body>\n";
		$result .= "         </oa:hasBody>\n";
		$result .= "   </oa:Annotation>";

		return $result;
	}
}

?>