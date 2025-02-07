<?php

include_once('Manager.php');
include_once('utilities/OccurrenceUtil.php');
include_once('utilities/UuidFactory.php');

class OmIdentifiers extends Manager
{
    private $identifierID = null;
    private $occid = null;
    private $schemaMap = array();
    private $parameterArr = array();
    private $typeStr = '';

    public function __construct($conn)
    {
        parent::__construct(null, 'write', $conn);
        $this->schemaMap = array(
            'occid' => 'i',
            'identifierValue' => 's',
            'identifierName' => 's',
            'format' => 's',
            'notes' => 's',
            'sortBy' => 'i',
            'recordID' => 's',
        );
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function insertIdentifier($inputArr)
    {
        $status = false;
        if ($this->occid) {
            if (!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
            $sql = 'INSERT INTO omoccuridentifiers (occid, modifiedUid';
            $sqlValues = '?, ?, ';
            $paramArr = array($this->occid, $GLOBALS['SYMB_UID']);
            $this->typeStr = 'is';
            if (array_key_exists('occid', $inputArr)) {
                unset($inputArr['occid']);
            }
            if (array_key_exists('modifiedUid', $inputArr)) {
                unset($inputArr['modifiedUid']);
            }
            $this->setParameterArr($inputArr);
            foreach ($this->parameterArr as $fieldName => $value) {
                $sql .= ', ' . $fieldName;
                $sqlValues .= '?, ';
                $paramArr[] = $value;
            }
            $sql .= ') VALUES(' . trim($sqlValues, ', ') . ') ';
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param($this->typeStr, ...$paramArr);
                try {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows || !$stmt->error) {
                            $this->identifierID = $stmt->insert_id;
                            $status = true;
                        } else $this->errorMessage = 'ERROR inserting omoccuridentifiers record (2): ' . $stmt->error;
                    } else $this->errorMessage = 'ERROR inserting omoccuridentifiers record (1): ' . $stmt->error;
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == '1062' || $e->getCode() == '1406') {
                        $this->errorMessage = $e->getMessage();
                    } else {
                        throw $e;
                    }
                }
                $stmt->close();
            } else $this->errorMessage = 'ERROR preparing statement for omoccuridentifiers insert: ' . $this->conn->error;
        }
        return $status;
    }

    private function setParameterArr($inputArr)
    {
        foreach ($this->schemaMap as $field => $type) {
            $postField = '';
            if (isset($inputArr[$field])) $postField = $field;
            elseif (isset($inputArr[strtolower($field)])) $postField = strtolower($field);
            if ($postField) {
                $value = trim($inputArr[$postField]);
                if ($value) {
                    $postField = strtolower($postField);
                    if ($postField == 'modifiedTimestamp') $value = OccurrenceUtil::formatDate($value);
                    if ($postField == 'modifieduid') $value = OccurrenceUtil::verifyUser($value, $this->conn);
                    if ($postField == 'sortBy') { // @TODO ? sortBy same a sortsequence?
                        if (!is_numeric($value)) $value = 10;
                    }
                } else $value = null;
                $this->parameterArr[$field] = $value;
                $this->typeStr .= $type;
            }
        }
        if (isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
    }

    public function updateIdentifier($inputArr)
    {
        $status = false;
        if ($this->occid && $this->conn) {
            $occidPlaceholder = null;
            $identifierNamePlaceholder = null;
            if (array_key_exists('occid', $inputArr)) {
                $occidPlaceholder = (int)$inputArr['occid'];
                unset($inputArr['occid']);
            }
            if (array_key_exists('identifierName', $inputArr)) {
                $identifierNamePlaceholder = $inputArr['identifierName'];
                unset($inputArr['identifierName']);
            }
            $paramArr = array();
            $paramArr[] = $GLOBALS['SYMB_UID'];
            $this->typeStr .= 'i';
            $this->setParameterArr($inputArr);
            $sqlFrag = '';
            foreach ($this->parameterArr as $fieldName => $value) {
                if ($fieldName !== 'occid' || $fieldName !== 'identifierName') {
                    $sqlFrag .= $fieldName . ' = ?, ';
                    if ($fieldName == 'modifiedUid' && empty($value)) {
                        $value = $GLOBALS['SYMB_UID'];
                    }
                    $paramArr[] = $value;
                }
            }
            $paramArr[] = $occidPlaceholder;
            $paramArr[] = $identifierNamePlaceholder;
            $this->typeStr .= 'is';
            $sql = 'UPDATE IGNORE omoccuridentifiers SET modifiedTimestamp = now(), modifiedUid = ? , ' . trim($sqlFrag, ', ') . ' WHERE (occid = ? AND identifierName = ?)';
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param($this->typeStr, ...$paramArr);
                $stmt->execute();
                if ($stmt->affected_rows || !$stmt->error) $status = true;
                else $this->errorMessage = 'ERROR updating omoccurassociations record: ' . $stmt->error;
                $stmt->close();
                $this->typeStr = '';
            } else $this->errorMessage = 'ERROR preparing statement for updating omoccurassociations: ' . $this->conn->error;
        }
        return $status;
    }

    public function getIdentifier($occid, $identifierName)
    {
        $idomoccuridentifiers = null;
        $sql = 'SELECT idomoccuridentifiers FROM omoccuridentifiers WHERE occid = ? AND identifierName = ?';
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('is', $occid, $identifierName);
            $stmt->execute();
            $stmt->bind_result($idomoccuridentifiers);
            $stmt->fetch();
            $stmt->close();
        }
        return $idomoccuridentifiers;
    }

    public function setOccid($id)
    {
        if (is_numeric($id)) $this->occid = $id;
    }

    public function getSchemaMap()
    {
        return $this->schemaMap;
    }

    public function deleteIdentifier()
    {
        if ($this->identifierID) {
            $sql = 'DELETE FROM omoccuridentifiers WHERE idomoccuridentifiers = ' . $this->identifierID;
            if ($this->conn->query($sql)) {
                return true;
            } else {
                $this->errorMessage = 'ERROR deleting omoccuridentifiers record: ' . $this->conn->error;
                return false;
            }
        }
    }

    public function setIdentifierID($id)
    {
        if (is_numeric($id)) $this->identifierID = $id;
    }
}
