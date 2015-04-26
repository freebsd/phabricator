<?php

class AddMaintainerReview extends HeraldCustomAction {

  public function appliesToAdapter(HeraldAdapter $adapter) {
	return $adapter instanceof HeraldDifferentialRevisionAdapter ||
		$adapter instanceof HeraldCommitAdapter;
  }

  public function appliesToRuleType($rule_type) {
	  return $rule_type == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL ||
		  $rule_type == HeraldRuleTypeConfig::RULE_TYPE_OBJECT;
  }

  public function getActionKey() {
	return 'custom:add-maintainer';
  }

  public function getActionName() {
	return 'Add port maintainer to CC';
  }

  public function getActionType() {
	return HeraldAdapter::VALUE_NONE;
  }

  public function applyEffect(
    HeraldAdapter $adapter,
    $object,
    HeraldEffect $effect) {

	$affected_paths = $adapter->getHeraldField(HeraldDifferentialRevisionAdapter::FIELD_DIFF_FILE);

	$handle = fopen("/usr/local/www/phabricator/INDEX-10", "r");

	$maintainer_search = array();

	while ($indexinfo = fgetcsv($handle, 0, "|")) {
		$port = implode("/", array_slice(explode("/", $indexinfo[1]), -2));
		$maintainer = $indexinfo[5];
		foreach ($affected_paths as $af_path) {
			if (strpos($af_path, $port) !== FALSE) {
				array_push($maintainer_search, $maintainer);
			}
		}
	}
	fclose($handle);

	$viewer = PhabricatorUser::getOmnipotentUser();
	$people_users = id(new PhabricatorPeopleQuery())
		->withEmails($maintainer_search)
		->setViewer($viewer)
		->execute();
	if (count($people_users) == 0) {
		return new HeraldApplyTranscript($effect, false, pht("no maintainers to add"));
	}

	$which_phids = mpull($people_users, 'getPHID', 'getUsername');

	foreach ($which_phids as $fbid) {
		$this->newCCs[$fbid] = true;
	}

	$str_result = pht('adding [%s] to CC', implode(', ', $maintainer_search));

	return new HeraldApplyTranscript($effect, true, $str_result);
   }

}

?>
