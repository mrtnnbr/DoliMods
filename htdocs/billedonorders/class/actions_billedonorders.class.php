<?php
/* Copyright (C) 2011-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/billedonorders/class/actions_billedonorders.class.php
 *	\ingroup    billedonorders
 *	\brief      File to control actions
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	Class to manage hooks for module BilledOnOrders
 */
class ActionsBilledOnOrders
{
    var $db;
    var $error;
    var $errors=array();

    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }


    
    /**
     * Complete doc forms
     *
     * @param	array	$parameters		Array of parameters
     * @param	object	$object			Object
     * @return	string					HTML content to add by hook
     */
    function printFieldListTitle($parameters,&$object)
    {
        global $conf, $langs;
        global $param, $sortfield, $sortorder;
        $langs->load("billedonorders@billedonorders");
        if (empty($conf->global->BILLEDONORDERS_DISABLE_BILLEDWOTAX)) 
            print_liste_field_titre($langs->trans("AmountBilledHT"),$_SERVER["PHP_SELF"],'','',$param,' align="right"',$sortfield,$sortorder);
        if (empty($conf->global->BILLEDONORDERS_DISABLE_BILLED)) 
            print_liste_field_titre($langs->trans("AmountBilledTTC"),$_SERVER["PHP_SELF"],'','',$param,' align="right"',$sortfield,$sortorder);
        if (empty($conf->global->BILLEDONORDERS_DISABLE_PAYED))
            print_liste_field_titre($langs->trans("AlreadyPaid"),$_SERVER["PHP_SELF"],'','',$param,' align="right"',$sortfield,$sortorder);
        if (empty($conf->global->BILLEDONORDERS_DISABLE_REMAINTOPAY))
            print_liste_field_titre($langs->trans("RemainderToPay"),$_SERVER["PHP_SELF"],'','',$param,' align="right"',$sortfield,$sortorder);
    
        return 0;
    }

    /**
     * Complete doc forms
     *
     * @param	array	$parameters		Array of parameters
     * @param	object	$object			Object
     * @return	string					HTML content to add by hook
     */
    function printFieldListOption($parameters,&$object)
    {
        global $conf, $langs;
        //global $param, $sortfield, $sortorder;
        if (empty($conf->global->BILLEDONORDERS_DISABLE_BILLEDWOTAX)) 
        {
            //print '<td align="right"><input type="text" name="billedonorders_billed" style="max-width:50px" class="flat maxwidth50" value="'.GETPOST('billedonorders_billed').'"></td>';
            print '<td class="liste_titre" align="right"></td>';
        }
        if (empty($conf->global->BILLEDONORDERS_DISABLE_BILLED)) 
        {
            //print '<td align="right"><input type="text" name="billedonorders_billed" style="max-width:50px" class="flat maxwidth50" value="'.GETPOST('billedonorders_billed').'"></td>';
            print '<td class="liste_titre" align="right"></td>';
        }
        if (empty($conf->global->BILLEDONORDERS_DISABLE_PAYED)) 
        {
            //print '<td align="right"><input type="text" name="billedonorders_payed" style="max-width:50px" class="flat maxwidth50" value="'.GETPOST('billedonorders_payed').'"></td>';
            print '<td class="liste_titre" align="right"></td>';
        }
        if (empty($conf->global->BILLEDONORDERS_DISABLE_REMAINTOPAY)) 
        {
            //print '<td align="right"><input type="text" name="billedonorders_remaintopay" style="max-width:50px" class="flat maxwidth50" value="'.GETPOST('billedonorders_remaintopay').'"></td>';
            print '<td class="liste_titre" align="right"></td>';
        }
    
        return 0;
    }

    /**
     * Complete doc forms
     *
     * @param	array	$parameters		Array of parameters
     * @param	object	$object			Object
     * @return	string					HTML content to add by hook
     */
    function printFieldListValue($parameters,&$object)
    {
        global $conf, $langs;
        global $db;
        //global $param, $sortfield, $sortorder;

        global $ordertmpforloop;
        if (! is_object($ordertmpforloop))
        {
            $ordertmpforloop = new Commande($db);
        }
        global $invoicetmpforloop;
        if (! is_object($invoicetmpforloop))
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $invoicetmpforloop = new Facture($db);
        }
        
        $billedht=0;
        $billedttc=0;
        $payed=0;
        $remaintopay=0;
        //$warning='';  TODO Check if invoice is used for more than one order
        if (is_object($parameters['obj']))
        {
            $id = $parameters['obj']->rowid ? $parameters['obj']->rowid : $parameters['obj']->id;
            if ($id > 0)
            {
                $ordertmpforloop->fetch($id);
                $ordertmpforloop->fetchObjectLinked($id, 'commande', 0, 'facture');
                $linkedobj1 = $ordertmpforloop->linkedObjectsIds;
                $ordertmpforloop->fetchObjectLinked(0, 'facture', $id, 'commande');
                $linkedobj2 = $ordertmpforloop->linkedObjectsIds;
                //$linkedobj2 = $ordertmpforloop->fetchObjectLinked($id, 'commande');
                $linkedobj = array_merge($linkedobj1, $linkedobj2);
                //var_dump($linkedobj);
            }
        }
        foreach($linkedobj as $types)
        {
            foreach($types as $key => $val)
            {
                //var_dump($val);
                $result = $invoicetmpforloop->fetch($val);
                if ($result > 0)
                {
                    if ($invoicetmpforloop->statut != $invoicetmpforloop::STATUS_DRAFT)
                    {
                        $billedht += $invoicetmpforloop->total_ht;
                        $billedttc += $invoicetmpforloop->total_ttc;
                        $paymentarray = $invoicetmpforloop->getListOfPayments();
                        foreach($paymentarray as $key2 => $val2)
                        {
                            //var_dump($val2);
                            $payed += $val2['amount'];
                        }
                    }
                }
                else
                {
                    dol_print_error($db);
                }
            }
        }
        $remaintopay = $billedttc - $payed;
        
        if (empty($conf->global->BILLEDONORDERS_DISABLE_BILLEDWOTAX)) 
        {
            print '<td align="right">'.($billedht?price($billedht):'');
            if ($billedht && $parameters['obj']->total_ht != $billedht)
            {
                print img_warning($langs->trans("AmountBilledDiffersFromAmountOnOrder"));
            }
            print '</td>';
        }
        if (empty($conf->global->BILLEDONORDERS_DISABLE_BILLED)) 
        {
            print '<td align="right">'.($billedttc?price($billedttc):'');
            if ($billedttc && $parameters['obj']->total_ttc != $billedttc)
            {
                print img_warning($langs->trans("AmountBilledDiffersFromAmountOnOrder"));
            }
            print '</td>';
        }
        if (empty($conf->global->BILLEDONORDERS_DISABLE_PAYED)) print '<td align="right">'.($payed?price($payed):'').'</td>';
        if (empty($conf->global->BILLEDONORDERS_DISABLE_REMAINTOPAY)) print '<td align="right">'.($remaintopay?price($remaintopay):'').'</td>';
        
        return 0;
    }    

}