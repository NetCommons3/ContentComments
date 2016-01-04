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
 * @var SessionComponent
 */
	public $Session = null;

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
		$this->controller = $controller;
	}

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param Controller $controller Controller with components to startup
 * @return void
 * @link http://book.cakephp.org/2.0/ja/controllers/components.html#Component::startup
 */
	public function startup(Controller $controller) {

		// コンポーネントから他のコンポーネントを使用する
		$collection = new ComponentCollection();
		$this->Session = new SessionComponent($collection);

		// コンテントコメントからエラーメッセージを受け取る仕組み http://skgckj.hateblo.jp/entry/2014/02/09/005111
		if ($this->Session->read('errors')) {
			foreach ($this->Session->read('errors') as $model => $errors) {
				$controller->$model->validationErrors = $errors;
			}
			// 表示は遷移・リロードまでの1回っきりなので消す
			$this->Session->delete('errors');
		}
	}

/**
 * コメントする
 *
 * @return bool 成功 or 失敗
 */
	public function comment() {
		// コンテンツコメントの処理名をパースして取得
		if (!$process = $this->__parseProcess()) {
			return false;
		}
		// パーミッションがあるかチェック
		if (!$this->__checkPermission($process)) {
			return false;
		}

		// 登録・編集・承認
		if ($process == $this::PROCESS_ADD ||
			$process == $this::PROCESS_EDIT ||
			$process == $this::PROCESS_APPROVED) {

			// dataの準備
			$data = $this->__readyData($process);

//$this->log($this->controller->request->data, 'debug');
			// コンテンツコメントのデータ保存
			if (!$this->controller->ContentComment->saveContentComment($data)) {
//$this->log($this->controller->ContentComment->validationErrors, 'debug');
				$this->controller->NetCommons->handleValidationError($this->controller->ContentComment->validationErrors);
//$this->log($this->controller->validationErrors, 'debug');
				// 別プラグインにエラーメッセージを送るため  http://skgckj.hateblo.jp/entry/2014/02/09/005111
				$this->controller->Session->write('errors.ContentComment', $this->controller->ContentComment->validationErrors);

				// 正常
			} else {
				// 下記は悪さをしないため、if文 で分岐しない
				// 登録用：入力欄のコメントを空にする
				unset($this->controller->request->data['ContentComment']['comment']);

				// 編集用：編集処理を取り除く（編集後は、対象コメントの入力欄を開けないため）
				unset($this->controller->request->data['process_' . ContentCommentsComponent::PROCESS_EDIT]);
			}

			// 削除
		} elseif ($process == $this::PROCESS_DELETE) {
			// コンテンツコメントの削除
			if (!$this->controller->ContentComment->deleteContentComment($this->controller->data['ContentComment']['id'])) {
				return false;
			}
		}
		return true;
	}

/**
 * コメントの処理名をパースして取得
 *
 * @throws BadRequestException
 * @return int どの処理
 */
	private function __parseProcess() {
		if ($matches = preg_grep('/^process_\d/', array_keys($this->controller->data))) {
			list(, $process) = explode('_', array_shift($matches));
		} else {
			if ($this->controller->request->is('ajax')) {
				$this->controller->renderJson(
					['error' => ['validationErrors' => ['status' => __d('net_commons', 'Invalid request.')]]],
					__d('net_commons', 'Bad Request'), 400
				);
			} else {
				throw new BadRequestException(__d('net_commons', 'Bad Request'));
			}
			return false;
		}

		return $process;
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
				$this->controller->data['ContentComment']['created_user'] == (int)AuthComponent::user('id')
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
 * @param int $process どの処理
 * @return array data
 */
	private function __readyData($process) {
		$data['ContentComment'] = $this->controller->request->data('ContentComment');
		$data['ContentComment']['block_key'] = Current::read('Block.key');

		// DBのcontent_commentsテーブルにはない項目なので取り除く
		unset($data['ContentComment']['use_comment_approval']);
		unset($data['ContentComment']['redirect_url']);

		// 登録処理
		if ($process == $this::PROCESS_ADD) {
			if (Current::permission('content_comment_publishable')) {
				// 公開
				$status = ContentComment::STATUS_PUBLISHED;
			} else {
				// コメント承認機能 0:使わない=>公開 1:使う=>未承認
				$status = $this->controller->request->data('ContentComment.use_comment_approval') ? ContentComment::STATUS_APPROVED: ContentComment::STATUS_PUBLISHED;
			}
			$data['ContentComment']['status'] = $status;

			// 承認処理
		} elseif ($process == $this::PROCESS_APPROVED) {
			$data['ContentComment']['status'] = ContentComment::STATUS_PUBLISHED; // 公開
		}
		// 編集処理は何もしない

		return $data;
	}
}
