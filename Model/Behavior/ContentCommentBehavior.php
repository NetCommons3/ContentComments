<?php
/**
 * コンテンツコメント Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('WorkflowComponent', 'Workflow.Controller/Component');

/**
 * Summary for ContentComment Behavior
 */
class ContentCommentBehavior extends ModelBehavior {

/**
 * @var bool 削除済みか
 */
	private $__isDeleted = null;

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
		$this->__isDeleted = false;
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
		if ($model->recursive == -1) {
			return $results;
		}

		// コンテンツコメント件数をセット
		$contentKeys = array();
		foreach ($results as &$result) {
			$result['ContentCommentCnt'] = array(
				'cnt' => 0
			);
			$contentKey = $result[$model->alias]['key'];
			$contentKeys[] = $contentKey;
		}

		$ContentComment = ClassRegistry::init('ContentComments.ContentComment');

		/* @see ContentComment::getConditions() */
		$conditions = $ContentComment->getConditions($contentKeys);

		// バーチャルフィールドを追加
		/* @link http://book.cakephp.org/2.0/ja/models/virtual-fields.html#sql */
		$ContentComment->virtualFields['cnt'] = 0;

		$contentCommentCnts = $ContentComment->find('all', array(
			'recursive' => -1,
			'fields' => array('content_key', 'count(content_key) as ContentComment__cnt'),	// Model__エイリアスにする
			'conditions' => $conditions,
			'group' => array('content_key'),
			'callbacks' => false,
		));

		foreach ($results as &$result) {
			$contentKey = $result[$model->alias]['key'];
			foreach ($contentCommentCnts as $contentCommentCnt) {
				if ($contentKey == $contentCommentCnt['ContentComment']['content_key']) {
					$result['ContentCommentCnt']['cnt'] = $contentCommentCnt['ContentComment']['cnt'];
					break;
				}
			}
		}

		// 公開権限なし
		if (! Current::permission('content_comment_publishable')) {
			return $results;
		}

		// --- 未承認件数の取得
		// 未承認のみ
		$conditions['ContentComment.status'] = WorkflowComponent::STATUS_APPROVED;

		// バーチャルフィールドを追加
		$ContentComment->virtualFields['approval_cnt'] = 0;

		$approvalCnts = $ContentComment->find('all', array(
			'recursive' => -1,
			'fields' => array('content_key', 'count(content_key) as ContentComment__approval_cnt'),	// Model__エイリアスにする
			'conditions' => $conditions,
			'group' => array('content_key'),
			'callbacks' => false,
		));

		foreach ($results as &$result) {
			$contentKey = $result[$model->alias]['key'];
			foreach ($approvalCnts as $approvalCnt) {
				if ($contentKey == $approvalCnt['ContentComment']['content_key']) {
					$result['ContentCommentCnt']['approval_cnt'] = $approvalCnt['ContentComment']['approval_cnt'];
					break;
				}
			}
		}

		return $results;
	}

/**
 * beforeDelete
 * コンテンツが削除されたら、書いてあったコメントも削除
 *
 * @param Model $model Model using this behavior
 * @param bool $cascade If true records that depend on this record will also be deleted
 * @return mixed False if the operation should abort. Any other result will continue.
 * @throws InternalErrorException
 * @link http://book.cakephp.org/2.0/ja/models/behaviors.html#ModelBehavior::beforedelete
 * @link http://book.cakephp.org/2.0/ja/models/callback-methods.html#beforedelete
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function beforeDelete(Model $model, $cascade = true) {
		// 多言語のコンテンツを key を使って、Model::deleteAll() で削除した場合を想定
		// 削除済みなら、もう処理をしない
		if ($this->__isDeleted) {
			return;
		}

		// コンテンツ取得
		$content = $model->find('first', array(
			'conditions' => array($model->alias . '.id' => $model->id)
		));

		$model->loadModels([
			'ContentComment' => 'ContentComments.ContentComment',
		]);

		// コンテンツコメント 削除
		if (! $model->ContentComment->deleteAll(array($model->ContentComment->alias . '.content_key' => $content[$model->alias]['key']), false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		$this->__isDeleted = true;
		return true;
	}
}