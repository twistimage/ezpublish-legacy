<?php
//
// Created on: <01-Aug-2002 09:58:09 bf>
//
// Copyright (C) 1999-2003 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/home/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
include_once( "lib/ezutils/classes/ezhttptool.php" );
include_once( "kernel/classes/ezcontentclass.php" );
include_once( "kernel/classes/ezcontentclassattribute.php" );
include_once( "kernel/classes/ezcontentobject.php" );
include_once( "kernel/classes/ezcontentobjectattribute.php" );




$currentUser =& eZUser::currentUser();
$currentUserID = $currentUser->attribute( "contentobject_id" );
$http =& eZHTTPTool::instance();
$Module =& $Params["Module"];

if ( isset( $Params["UserID"] ) )
    $UserID = $Params["UserID"];

$ini =& eZINI::instance();
$classID = $ini->variable( 'UserSettings', 'UserClassID' );
//$classID = 4;
$userAccount =& eZUser::fetch( $UserID );
$userClass =& eZContentClass::fetch( $classID );
$userClassAttributes =& eZContentClassAttribute::fetchFilteredList( array( 'contentclass_id' => $classID, 'version' => 0 ) );
$userObject =& eZContentObject::fetch( $UserID );
$currentVersion = $userObject->attribute( 'current_version' );
$userProfile = array();
foreach ( $userClassAttributes as $userClassAttribute )
{
    $classAttributeID = $userClassAttribute->attribute( 'id' );
    $classAttributeName = $userClassAttribute->attribute( 'name' );
    $userObjectAttribute =& eZContentObjectAttribute::fetchObject( eZContentObjectAttribute::definition(),
                                                                   null,
                                                                   array( 'contentobject_id' => $UserID,
                                                                          'contentclassattribute_id' => $classAttributeID,
                                                                          'version' => $currentVersion ) );
    $objectAttributeContent = $userObjectAttribute->attribute( 'data_text' );
    if( $objectAttributeContent != "")
    {
        $item = array( "name" => $classAttributeName,
                       "value" => $objectAttributeContent,
                       "classAttribute_id" => $classAttributeID  );
        $userProfile[] = $item;
    }
}

if ( $http->hasPostVariable( "UpdateProfileButton" ) )
{
    foreach ( $userProfile as $profile )
    {
        if ( $http->hasPostVariable( "ContentclassAttribute_" . $profile['classAttribute_id'] ) )
        {
            $name = $profile['name'];
            $classAttributeID = $profile['classAttribute_id'];
            $value = $http->postVariable( "ContentclassAttribute_" . $profile['classAttribute_id'] );
            $objectAttribute =& eZContentObjectAttribute::fetchObject( eZContentObjectAttribute::definition(),
                                                                       null,
                                                                       array( 'contentobject_id' => $UserID,
                                                                              'contentclassattribute_id' => $classAttributeID,
                                                                              'version' => $currentVersion ) );
            $objectAttribute->setAttribute( 'data_text', $value );
            $objectAttribute->store();
        }
    }
    if ( $http->hasPostVariable( "email" ) )
    {
        $email = $http->postVariable( "email" );
        $userAccount->setAttribute( "email", $email );
        $userAccount->store();
    }

    $userObject =& eZContentObject::fetch( $UserID );
    $objectName = $userClass->contentObjectName( $userObject );
    $userObject->setName( $objectName );
    $userObject->store();
    $Module->redirectTo( '/content/view/sitemap/5/' );
    return;
}

if ( $http->hasPostVariable( "ChangePasswordButton" ) )
{
    $Module->redirectTo( $Module->functionURI( "password" ) . "/" . $UserID  );
    return;
}

if ( $http->hasPostVariable( "ChangeSettingButton" ) )
{
    $Module->redirectTo( $Module->functionURI( "setting" ) . "/" . $UserID );
    return;
}

if ( $http->hasPostVariable( "CancelButton" ) )
{
    $Module->redirectTo( '/content/view/sitemap/5/' );
    return;
}

$Module->setTitle( "Edit user information" );
// Template handling
include_once( "kernel/common/template.php" );
$tpl =& templateInit();
$tpl->setVariable( "module", $Module );
$tpl->setVariable( "http", $http );
$tpl->setVariable( "userID", $UserID );
$tpl->setVariable( "userAccount", $userAccount );
$tpl->setVariable( "userProfile", $userProfile );

$Result = array();
$Result['content'] =& $tpl->fetch( "design:user/edit.tpl" );

?>
