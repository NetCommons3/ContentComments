<?php
/**
 * コンテンツコメント一覧の1件表示＆編集 template
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * @param string $pluginKey プラグインキー
 * @param string $contentKey コンテントキー
 * @param array $contentComment コンテンツコメント一覧の1件データ
 * @param string $contentTitleForMail メールのためのコンテンツタイトル
 * @param bool $useCommentApproval コンテントコメント承認利用フラグ
 */
$this->NetCommonsHtml->css(array('/content_comments/css/style.css'));
?>
<div class="row">
	<div class="col-xs-6">
		<?php if ($contentComment['ContentComment']['created_user']) : ?>
			<?php echo $this->DisplayUser->handleLink($contentComment, ['avatar' => true]); ?>
		<?php else : ?>
			<?php echo __d('content_comments', 'Guest(Not logged in)'); ?>
		<?php endif; ?>

		<?php /* ステータス */ ?>
		<?php echo $this->Workflow->label($contentComment['ContentComment']['status'], array(
			WorkflowComponent::STATUS_APPROVAL_WAITING => array(
				'class' => 'label-warning',
				'message' => __d('content_comments', 'Approving'),
			),
		)); ?>
	</div>
	<div class="col-xs-6 text-right">
		<small class="text-muted"><?php echo $this->Date->dateFormat($contentComment['ContentComment']['created']); ?></small>
	</div>
</div>

<?php /* コメント表示 */ ?>
<div ng-hide="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?>">
	<?php echo nl2br(h($contentComment['ContentComment']['comment'])); ?>
</div>

<?php /* 編集許可あり or (自分で投稿したコメント & ログイン済みなら、編集可能) */ ?>
<?php if (Current::permission('content_comment_editable') || (
		Current::permission('content_comment_creatable') &&
		$contentComment['ContentComment']['created_user'] == (int)Current::read('User.id') &&
		Current::read('User'))): ?>
	<?php /* 編集 */ ?>
	<?php echo $this->element('ContentComments.edit', array(
		'pluginKey' => $pluginKey,
		'contentKey' => $contentKey,
		'contentComment' => $contentComment,
		'useCommentApproval' => $useCommentApproval,
		'contentTitleForMail' => $contentTitleForMail,
	)); ?>
<?php endif;
