<?php
/**
 * コンテントコメント Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('ContentCommentsAppController', 'ContentComments.Controller');

/**
 * コンテントコメント Controller
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\ContentComments\Controller
 */
class ContentCommentsController extends ContentCommentsAppController {

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'ContentComments.ContentComment',
	);

/**
 * use components
 *
 * @var array
 */
	public $components = array(
		'ContentComments.ContentComments',
		'NetCommons.Permission' => array(
			//アクセスの権限
			'allow' => array(
				'add' => 'content_comment_creatable',
				'edit' => 'content_comment_creatable',
				'approve' => 'content_comment_publishable',
				'delete' => 'content_comment_creatable'
			),
		),
	);

/**
 * beforeFilter
 *
 * @return void
 * @see NetCommonsAppController::beforeFilter()
 */
	public function beforeFilter() {
		parent::beforeFilter();

		// ブロック未選択は、何も表示しない
		if (! Current::read('Block.id')) {
			$this->setAction('emptyRender');
			return false;
		}

		$isVisitorCreatable = $this->request->data('_tmp.is_visitor_creatable');

		// ビジターまで投稿OKなら、ログインなしでもコメント投稿できる
		if ($this->action == 'add' && $isVisitorCreatable) {
			// 暫定対応：Permissionコンポーネント外す https://github.com/NetCommons3/NetCommons3/issues/198
			$this->Components->unload('NetCommons.Permission');
			// ゲストアクセスOKのアクションを設定
			$this->Auth->allow('add');
		}
	}

/**
 * 登録
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			// コメントする
			if (!$this->ContentComments->comment()) {
				$this->throwBadRequest();
				return;
			}

			$message = __d('content_comments', 'Commented.');
			$this->__setFlashMessageAndRedirect($message);
		}
	}

/**
 * 編集
 *
 * @return CakeResponse
 */
	public function edit() {
		if ($this->request->is('put')) {
			// コメントする
			if (!$this->ContentComments->comment()) {
				$this->throwBadRequest();
				return;
			}

			$message = __d('content_comments', 'Edit the comment.');
			$this->__setFlashMessageAndRedirect($message);
		}
	}

/**
 * 承認
 *
 * @return CakeResponse
 */
	public function approve() {
		if ($this->request->is('put')) {
			// コメントする
			if (!$this->ContentComments->comment()) {
				$this->throwBadRequest();
				return;
			}

			$message = __d('content_comments', 'Approved the comment.');
			$this->__setFlashMessageAndRedirect($message);
		}
	}

/**
 * 削除
 *
 * @return CakeResponse
 */
	public function delete() {
		if ($this->request->is('delete')) {
			// コメントする
			if (!$this->ContentComments->comment()) {
				$this->throwBadRequest();
				return;
			}

			$message = __d('content_comments', 'Comment has been deleted.');
			$this->__setFlashMessageAndRedirect($message);
		}
	}

/**
 * _setFlashMessageAndRedirect
 *
 * @param string $message flash error message
 * @return void
 */
	private function __setFlashMessageAndRedirect($message) {
		// エラーなし
		if (!$this->ContentComment->validationErrors) {
			$status = $this->request->data('ContentComment.status');

			// 承認依頼ならワーニング表示
			if ($status == WorkflowComponent::STATUS_APPROVAL_WAITING) {
				$message = __d('content_comments',
					'Received your comment. It does not appear until it has been approved.');
				$this->NetCommons->setFlashNotification($message, array(
					'class' => 'warning',
					'interval' => NetCommonsComponent::ALERT_VALIDATE_ERROR_INTERVAL,
				));
			} else {
				$this->NetCommons->setFlashNotification($message, array(
					'class' => 'success',
					'interval' => NetCommonsComponent::ALERT_SUCCESS_INTERVAL,
				));
			}
		}

		// 一覧へ
		$this->redirect($this->request->referer(true));
	}
}

