<?php
// modified by jaoe1000

function getSession($valueName) {
  global $session;
  if (is_array($session) && isset($session[$valueName]))
    return $session[$valueName];
  else
    return false;
}

function getSessionUser($valueName) {
  global $session;
  if (is_array($session) && isset($session['user']) && is_array($session['user']) && isset($session['user'][$valueName]))
    return $session['user'][$valueName];
  else
    return false;
}

function getSessionSuperUser(): int {
  global $session;
  if (!is_array($session) || !isset($session['user']) || !is_array($session['user']) || !isset($session['user']['superuser'])) {
    return 0;
  }
  $su = $session['user']['superuser'];
  if (is_int($su)) {
    return $su;
  }
  if (is_string($su) && is_numeric($su)) {
    return (int)$su;
  }
  return 0;
}
