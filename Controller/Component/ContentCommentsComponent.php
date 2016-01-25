<?php
/**
 * ContentComments Component
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Component', 'Controller');
App::uses('ContentComment', 'ContentComments.Model');

App::uses('ComponentCollection', 'Controller');
App::uses('SessionComponent', 'Controller/Component');

/**
 * ContentComments Component
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\NetCommons\Controller\Component
 */
class ContentCommentsComponent extends Component {

/**
 * @var SessionComponent セッションコンポーネント
 */
	public $Session = null;

/**
 * @var Controller コントローラ
 */
	protected $_controller = null;

/**
 * @var int start limit
 */
	const START_LIMIT = 5;

/**
 * @var int max limit
 */
	const MAX_LIMIT = 100;

/**
 * @var string 登録処理
 */
	const PROCESS_ADD = '1';

/**
 * @var string 編集処理
 */
	const PROCESS_EDIT = '2';

/**
 * @var string 削除処理
 */
	const PROCESS_DELETE = '3';

/**
 * @var string 承認処理
 */
	const PROCESS_APPROVED = '4';

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Controller $controller Instantiating controller
 * @return void
 * @link http://book.cakephp.org/2.0/ja/controllers/components.html#Component::initialize
 */
	public function initialize(Controller $controller) {
		$this->_controller = $controller;

		// コンポーネントから他のコンポーネントを使用する
		$collection = new ComponentCollection();
		$this->Session = new SessionComponent($collection);
	}

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param Controller $controller Controller with components to startup
 * @return void
 * @link http://book.cakephp.org/2.0/ja/controllers/components.html#Component::startup
 */
	public function startup(Controller $controller) {
		// コンテントコメントからエラーメッセージを受け取る仕組み http://skgckj.hateblo.jp/entry/2014/02/09/005111
		if ($this->Session->read('errors')) {
			foreach ($this->Session->read('errors') as $model => $errors) {
				$this->_controller->$model->validationErrors = $errors;
			}
			// 表示は遷移・リロードまでの1回っきりなので消す
			$this->Session->delete('errors');
		}

		if ($this->Session->read('_tmp')) {
			if ($this->_controller->request->data('_tmp')) {
				$this->_controller->request->data['_tmp'] = Hash::merge($this->_controller->request->data('_tmp'), $this->Session->read('_tmp'));
			} else {
				$this->_controller->request->data['_tmp'] = $this->Session->read('_tmp');
			}
			// 表示は遷移・リロードまでの1回っきりなので消す
			$this->Session->delete('_tmp');
		}
	}

/**
 * Called before the Controller::beforeRender(), and before
 * the view class is loaded, and before Controller::render()
 *
 * @param Controller $controller Controller with components to beforeRender
 * @return void
 * @link http://book.cakephp.org/2.0/ja/controllers/components.html#Component::beforeRender
 * @throws Exception Paginatorによる例外
 */
	public function beforeRender(Controller $controller) {
		// コメント利用フラグのDB項目名, コンテンツキーのDB項目名, 許可アクション
		if (! isset($this->settings['viewVarsUseComment']) || ! isset($this->settings['viewVarsContentKey']) || ! isset($this->settings['allow'])) {
			return;
		}

		// 許可アクションなし
		if (!in_array($this->_controller->request->params['action'], $this->settings['allow'])) {
			return;
		}

		// コメントを利用しない
		if (!Hash::get($this->_controller->viewVars, $this->settings['viewVarsUseComment'][0])) {
			return;
		}

		// 条件
		$query['conditions'] = $this->__getConditions();

		//ソート
		$query['order'] = array('ContentComment.created' => 'desc');

		//表示件数
		$query['limit'] = $this::START_LIMIT;

		$this->_controller->Paginator->settings = $query;
		try {
			$contentComments = $this->_controller->Paginator->paginate('ContentComment');
		} catch (Exception $ex) {
			CakeLog::error($ex);
			throw $ex;
		}

		$this->_controller->request->data['ContentComments'] = $contentComments;
	}

/**
 * コメントする
 *
 * @return bool 成功 or 失敗
 */
	public function comment() {
		$process = $this->_controller->request->data('_tmp.process');

		// パーミッションがあるかチェック
		if (!$this->__checkPermission($process)) {
			return false;
		}

		// 登録・編集・承認
		if ($process == $this::PROCESS_ADD ||
			$process == $this::PROCESS_EDIT ||
			$process == $this::PROCESS_APPROVED) {

			// dataの準備
			$data = $this->__readyData();

			// コンテンツコメントのデータ保存
			if (!$this->_controller->ContentComment->saveContentComment($data)) {
				$this->_controller->NetCommons->handleValidationError($this->_controller->ContentComment->validationErrors);

				// 別プラグインにエラーメッセージとどの処理を送るため  http://skgckj.hateblo.jp/entry/2014/02/09/005111
				$this->Session->write('errors.ContentComment', $this->_controller->ContentComment->validationErrors);
				$this->Session->write('_tmp.process', $process);
				$this->Session->write('_tmp.ContentComment.id', $this->_controller->request->data('ContentComment.id'));
			}

			// 削除
		} elseif ($process == $this::PROCESS_DELETE) {
			// コンテンツコメントの削除
			if (!$this->_controller->ContentComment->deleteContentComment($this->_controller->request->data('ContentComment.id'))) {
				return false;
			}
		}
		return true;
	}

/**
 * Get conditions
 *
 * @return query conditions
 */
	private function __getConditions() {
		$conditions = array(
			'block_key' => Current::read('Block.key'),
			'plugin_key' => $this->_controller->request->params['plugin'],
			'content_key' => Hash::get($this->_controller->viewVars, $this->settings['viewVarsContentKey'][0])
		);

		// 公開権限あり
		if (Current::permission('content_comment_publishable')) {
			return $conditions;
		}

		// ログインしていない
		if (!(bool)AuthComponent::user()) {
			$conditions['ContentComment.status'] = ContentComment::STATUS_PUBLISHED;
			return $conditions;
		}

		// 公開権限なし、ログイン済み
		$addConditions = array(
			'OR' => array(
				'ContentComment.status' => ContentComment::STATUS_PUBLISHED,
				'ContentComment.created_user' => (int)AuthComponent::user('id'),
			)
		);
		$conditions = array_merge($conditions, $addConditions);

		return $conditions;
	}

/**
 * パーミッションがあるかチェック
 *
 * @param int $process どの処理
 * @return bool true:パーミッションあり or false:パーミッションなし
 */
	private function __checkPermission($process) {
		// 登録処理 and 投稿許可あり
		if ($process == $this::PROCESS_ADD && Current::permission('content_comment_creatable')) {
			return true;

			// (編集処理 or 削除処理) and (編集許可あり or 自分で投稿したコメントなら、編集・削除可能)
		} elseif (($process == $this::PROCESS_EDIT || $process == $this::PROCESS_DELETE) && (
				Current::permission('content_comment_editable') ||
				$this->_controller->data['ContentComment']['created_user'] == (int)AuthComponent::user('id')
		)) {
			return true;

			// 承認処理 and 承認許可あり
		} elseif ($process == $this::PROCESS_APPROVED && Current::permission('content_comment_publishable')) {
			return true;

		}
		return false;
	}

/**
 * dataの準備
 *
 * @return array data
 */
	private function __readyData() {
		$data['ContentComment'] = $this->_controller->request->data('ContentComment');
		$data['ContentComment']['block_key'] = Current::read('Block.key');

		return $data;
	}
}
