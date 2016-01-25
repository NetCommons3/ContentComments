<?php
/**
 * コンテンツコメント件数 Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('ContentComment', 'ContentComments.Model');

/**
 * Summary for ContentComment Behavior
 */
class ContentCommentCountBehavior extends ModelBehavior {

/**
 * @var array 設定
 */
	public $settings = array();

/**
 * setup
 *
 * @param Model $model モデル
 * @param array $settings 設定値
 * @return void
 * @link http://book.cakephp.org/2.0/ja/models/behaviors.html#ModelBehavior::setup
 */
	public function setup(Model $model, $settings = array()) {
		$this->settings[$model->alias] = $settings;
	}

/**
 * afterFind
 * コンテンツコメント件数をセット
 *
 * @param Model $model モデル
 * @param mixed $results Find結果
 * @param bool $primary primary
 * @return array $results
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function afterFind(Model $model, $results, $primary = false) {
		if (empty($results) || ! isset($results[0][$model->alias]['key'])) {
			return $results;
		}

		// コンテンツコメント件数をセット
		$contents = array();
		foreach ($results as $content) {
			$contentKey = $content[$model->alias]['key'];

			$content['ContentCommentCnt'] = array(
				'content_key' => $contentKey,
				'cnt' => 0
			);
			$contents[$contentKey] = $content;
		}

		$ContentComment = ClassRegistry::init('ContentComments.ContentComment');

		// バーチャルフィールドを追加  http://book.cakephp.org/2.0/ja/models/virtual-fields.html#sql
		$ContentComment->virtualFields['cnt'] = 0;

		$contentCommentCnts = $ContentComment->find('all', array(
			'recursive' => -1,
			'fields' => array('content_key', 'count(content_key) as ContentComment__cnt'),	// Model__エイリアスにする
			'conditions' => array(
				'plugin_key' => Current::read('Plugin.key'),
				'status' => ContentComment::STATUS_PUBLISHED,
				'content_key' => array_keys($contents)
			),
			'group' => array('content_key'),
		));

		foreach ($contentCommentCnts as $contentCommentCnt) {
			$contentKey = $contentCommentCnt['ContentComment']['content_key'];
			$contents[$contentKey]['ContentCommentCnt']['cnt'] = $contentCommentCnt['ContentComment']['cnt'];
		}
		$results = array_values($contents);

		return $results;
	}
}