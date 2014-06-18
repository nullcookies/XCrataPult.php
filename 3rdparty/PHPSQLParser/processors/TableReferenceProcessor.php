<?php
/**
 * TableReferenceProcessor.php
 *
 * This class processes the table_reference within the FROM part of an SQL statement.
 *
 * PHP version 5
 *
 * LICENSE:
 * Copyright (c) 2010-2014 Justin Swanhart and André Rothe
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author    André Rothe <andre.rothe@phosco.info>
 * @copyright 2010-2014 Justin Swanhart and André Rothe
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id: AbstractProcessor.php 1088 2014-02-25 08:58:52Z phosco@gmx.de $
 *
 */
require_once dirname(__FILE__) . '/AbstractProcessor.php';
require_once dirname(__FILE__) . '/ExpressionListProcessor.php';
require_once dirname(__FILE__) . '/DefaultProcessor.php';
require_once dirname(__FILE__) . '/../utils/ExpressionType.php';

/**
 * This class processes the table_reference within a FROM statement part.
 * 
 * @author  André Rothe <andre.rothe@phosco.info>
 * @license http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * 
 */
class TableReferenceProcessor extends AbstractProcessor {

    protected function processTableReference($tokens, $start) {
        $processor = new TableReferenceProcessor();
        return $processor->process(array_slice($tokens, $start, true));
    }
    
    public function process($tokens) {
        $base_expr = '';
        $result = array();

        foreach ($tokens as $k => $token) {

            $trim = trim($token);
            $base_expr .= $token;

            if ($skip !== 0) {
                $skip--;
                continue;
            }

            if ($trim === '') {
                continue;
            }

            $upper = strtoupper($trim);

            switch ($upper) {

            case '}':
                // end of OJ
                $currCategory = ExpressionType::ODBC_EXPRESSION;
                $skip = 1; // it must be OJ
                continue;

            default:
                switch ($currCategory) {
                    
                    case ExpressionType::ODBC_EXPRESSION:
                        // an ODBC reference
                        // start the parsing with the current token
                        $parsed = processTableReference($tokens, $k);
                        // set the skip 
                        break;
                        
                    default:
                        // a table_reference
                        $parsed = processTableReference($tokens, $k);
                        // set the skip 
                        break;
                }
                break;
            }
            $prevCategory = $currCategory;
            $currCategory = '';
        }

        return $result;
    }

}
?>