<?php

/**
 * This file is part of the Obo framework for application domain logic.
 * Obo framework is based on voluntary contributions from different developers.
 *
 * @link https://github.com/obophp/obo
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace obo\DataStorage\Interfaces;

interface IDataConverter {

    /**
     * @param string $combinationCode CombinatioCode has format 'D{databaseDataType}O{oboDataType}' or'O{oboDataType}D{databaseDataType}'
     * @return string returns the name of the convert method, which must be implemented in this dataConvertor
     */
    public function convertFilterForCombinationCode($combinationCode);

}
