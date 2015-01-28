#!/usr/bin/env php
<?php

$root = dirname(dirname(dirname(__FILE__)));
require_once $root.'/scripts/__init_script__.php';

echo "Examining users.\n";
foreach (new LiskMigrationIterator(new PhabricatorUser()) as $user) {
  $username = $user->getUsername();
  echo "Changing preferences for " . $username . "...";
  
  $preferences = $user->loadPreferences();
  $mailtags = getAllTags($user);
  
  $value_email = PhabricatorUserPreferences::MAILTAG_PREFERENCE_EMAIL;
  $value_notify = PhabricatorUserPreferences::MAILTAG_PREFERENCE_NOTIFY;
  $value_ignore = PhabricatorUserPreferences::MAILTAG_PREFERENCE_IGNORE;
  // start with a no-email default
  foreach ($mailtags as &$value) {
    $value = $value_ignore;
  }

  // only email on a few specific maniphest settings
  $mailtags[ManiphestTransaction::MAILTAG_STATUS] = $value_email;
  $mailtags[ManiphestTransaction::MAILTAG_OWNER] = $value_email;
  $mailtags[ManiphestTransaction::MAILTAG_PRIORITY] = $value_email;
  $mailtags[ManiphestTransaction::MAILTAG_COMMENT] = $value_email;
  $preferences->setPreference('mailtags', $mailtags);
  $preferences->setPreference(PhabricatorUserPreferences::PREFERENCE_NO_SELF_MAIL, 1);
  $preferences->save();
}

echo "\n";
echo "Done.\n";

function getAllTags(PhabricatorUser $user) {
  $tags = array();
  foreach (getAllEditorsWithTags($user) as $editor) {
    $tags += $editor->getMailTagsMap();
  }
  return $tags;
}

function getAllEditorsWithTags(PhabricatorUser $user) {
  $editors = id(new PhutilSymbolLoader())
    ->setAncestorClass('PhabricatorApplicationTransactionEditor')
    ->loadObjects();
  foreach ($editors as $key => $editor) {
    // Remove editors which do not support mail tags.
    if (!$editor->getMailTagsMap()) {
      unset($editors[$key]);
    }
    // Remove editors for applications which are not installed.
    $app = $editor->getEditorApplicationClass();
    if ($app !== null) {
      if (!PhabricatorApplication::isClassInstalledForViewer($app, $user)) {
        unset($editors[$key]);
      }
    }
  }
  return $editors;
}





