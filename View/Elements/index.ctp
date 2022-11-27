<?php
/**
 * コンテンツコメント一覧 template
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * @param string $contentKey コンテンツキー
 * @param string $contentTitleForMail メールのためのコンテンツタイトル
 * @param bool $useCommentApproval コンテントコメント承認利用フラグ
 * @param array $contentComments コンテンツコメント一覧データ
 * @param int $approvalCnt 未承認件数
 * @param bool $isVisitorCreatable ビジター投稿許可フラグ
 */
$this->NetCommonsHtml->css(array('/content_comments/css/style.css'));

// プラグインキー
$pluginKey = $this->request->params['plugin'];
?>
<?php /* コメント数 */ ?>
<div class="content-comments">
	<div class="comment-count">
		<label class="control-label" for="CommentComment">
			<span class="glyphicon glyphicon-comment" aria-hidden="true"></span>
			<?php
				echo sprintf(__d('content_comments', '%s comments'), $this->Paginator->param('count'));

				// 未承認1件以上
				if ($approvalCnt >= 1) {
					echo sprintf(__d('content_comments', '（%s unapproved）'), $approvalCnt);
				}
			?>
		</label>
	</div>
</div>

<?php /* コメント許可あり or ビジターまで投稿OKなら、ログインなしでもコメント投稿できる */ ?>
<?php if (Current::permission('content_comment_creatable') || $isVisitorCreatable): ?>
	<?php /* 登録 */ ?>
	<?php echo $this->element('ContentComments.add', array(
		'pluginKey' => $pluginKey,
		'contentKey' => $contentKey,
		'contentTitleForMail' => $contentTitleForMail,
		'useCommentApproval' => $useCommentApproval,
		'isVisitorCreatable' => $isVisitorCreatable
	)); ?>
<?php endif; ?>

<div id="nc-content-comments-<?php echo Current::read('Frame.id'); ?>">
	<div class="content-comments nc-content-list">
		<?php foreach ($contentComments as $contentComment): ?>
			<?php /* visitar対応 1件目 and 投稿許可なしで border-top 表示しない */ ?>
			<article class="comment">

				<?php /* 1件データ表示＆編集 */ ?>
				<?php echo $this->element('ContentComments.indexOnce', array(
					'pluginKey' => $pluginKey,
					'contentKey' => $contentKey,
					'contentComment' => $contentComment,
					'useCommentApproval' => $useCommentApproval,
					'contentTitleForMail' => $contentTitleForMail,
				)); ?>

				<div class="text-right" ng-hide="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?>">
					<?php /* 承認許可あり and 未承認のコメント  */ ?>
					<?php if (Current::permission('content_comment_publishable') && $contentComment['ContentComment']['status'] == WorkflowComponent::STATUS_APPROVAL_WAITING): ?>
						<?php /* 承認ボタン */ ?>
						<?php echo $this->element('ContentComments.approvalButton', array(
							'pluginKey' => $pluginKey,
							'contentKey' => $contentKey,
							'contentComment' => $contentComment,
							'contentTitleForMail' => $contentTitleForMail,
							'useCommentApproval' => $useCommentApproval,
						)); ?>
					<?php endif; ?>

					<?php /* 編集許可あり or (自分で投稿したコメント & ログイン済みなら、編集・削除可能) */ ?>
					<?php if (Current::permission('content_comment_editable') || (
							Current::permission('content_comment_creatable') &&
							$contentComment['ContentComment']['created_user'] == (int)Current::read('User.id') &&
							Current::read('User'))): ?>

						<?php /* 編集・削除ボタン */ ?>
						<?php echo $this->element('ContentComments.editAndDeleteButton', array(
							'pluginKey' => $pluginKey,
							'contentKey' => $contentKey,
							'contentComment' => $contentComment,
						)); ?>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach ?>
	</div>

	<?php /* ページャ */ ?>
	<?php echo $this->element('NetCommons.paginator', array(
		"url" => array(
			Current::read('Block.id'),
			$contentKey
		)
	)); ?>

</div>
