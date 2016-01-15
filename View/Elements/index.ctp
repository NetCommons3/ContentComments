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
 * @param string $contentKey コンテントキー
 * @param bool $useCommentApproval コンテントコメント承認利用フラグ
 * @param bool $useComment コンテンツコメント利用フラグ
 * @param int $contentCommentCnt コンテンツコメント件数
 * @param string $redirectUrl 操作後の遷移URL
 */
$this->NetCommonsHtml->css(array('/content_comments/css/style.css'));
$this->NetCommonsHtml->script(array('/content_comments/js/content_comments.js'));

// プラグインキー
$pluginKey = $this->request->params['plugin'];

// 操作後の遷移URL
$redirectUrl = isset($redirectUrl) ? $redirectUrl : Router::url();

// elementの引数 $contentComments 非推奨
$contentComments = isset($contentComments) ? $contentComments : array();
if (empty($contentComments)) {
	// 2016.1.6 $this->request->dataから取得するように修正
	$contentComments = $this->request->data('ContentComments') ? $this->request->data('ContentComments') : array();
}

// 2016.1.6 isCommentApproved -> useCommentApprovalにリネーム
$useCommentApproval = isset($useCommentApproval) ? $useCommentApproval : null;
if (isset($isCommentApproved)) {
	$useCommentApproval = $isCommentApproved;
}

foreach ($contentComments as $idx => $contentComment) {
	// ・未承認のコメントは表示しない。
	// ・自分のコメントは表示する。
	// ・承認許可ありの場合、表示する。
	if (Current::permission('content_comment_publishable') || $contentComment['ContentComment']['created_user'] == (int)AuthComponent::user('id')) {
		// 表示 => なにもしない
	} elseif ($contentComment['ContentComment']['status'] == ContentComment::STATUS_APPROVED) {
		// 非表示 => 配列から取り除く
		unset($contentComments[$idx]);
	}
}
?>

<?php /* コメントを利用しない or (コメント0件 and コメント投稿できない) */ ?>
<?php if (!$useComment || (!$contentComments && !Current::permission('content_comment_creatable'))): ?>
	<?php /* 表示しない */ ?>

<?php else : ?>
		<div class="row">
		<div class="col-xs-12">
			<article>
				<div class="panel panel-default">

					<?php /* 入力欄 */ ?>
					<?php echo $this->element('ContentComments.form', array(
						'pluginKey' => $pluginKey,
						'contentKey' => $contentKey,
						'useCommentApproval' => $useCommentApproval,
						'contentCommentCnt' => $contentCommentCnt,
						'redirectUrl' => $redirectUrl,
					)); ?>

					<div id="nc-content-comments-<?php echo Current::read('Frame.id'); ?>" ng-controller="ContentComments">
						<div class="content-comments">
							<?php $i = 0; ?>
							<?php foreach ($contentComments as $contentComment): ?>
								<?php /* visitar対応 1件目 and 投稿許可なしで border-top 表示しない */ ?>
								<article class="comment <?php echo $i >= ContentCommentsComponent::START_LIMIT ? 'hidden' : '' ?>
											 <?php echo $i == 0 && !Current::permission('content_comment_creatable') ? 'comment-no-form' : ''; ?>">
									<div class="media">
										<div class="pull-left">
											<?php /* アバター */ ?>
											<?php echo $this->DisplayUser->avatar($contentComment, array(
												'class' => '',
											)); ?>
										</div>
										<div class="media-body">
											<div class="row">
												<div class="col-xs-6">
													<?php echo $this->DisplayUser->handleLink($contentComment); ?>

													<?php /* ステータス */ ?>
													<?php echo $this->Workflow->label($contentComment['ContentComment']['status'], array(
														ContentComment::STATUS_APPROVED => array(
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
												<?php echo nl2br($contentComment['ContentComment']['comment']) ?>
											</div>

											<?php /* コンテンツコメント編集許可あり or 自分で投稿したコメントなら、編集可能 */ ?>
											<?php if (Current::permission('content_comment_editable') || $contentComment['ContentComment']['created_user'] == (int)AuthComponent::user('id')): ?>
												<?php /* 編集フォーム 非表示 */ ?>
												<div ng-show="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?>">
													<?php echo $this->NetCommonsForm->create('ContentComment', array(
														'name' => 'form',
														'url' => '/content_comments/content_comments/edit/' . Current::read('Frame.id'),
														'type' => 'put',
													)); ?>
														<?php echo $this->NetCommonsForm->hidden('ContentComment.id', array('value' => $contentComment['ContentComment']['id'])); ?>
														<?php echo $this->NetCommonsForm->hidden('ContentComment.created_user', array('value' => $contentComment['ContentComment']['created_user'])); ?>
														<?php echo $this->NetCommonsForm->hidden('ContentComment.plugin_key', array('value' => $pluginKey)); ?>
														<?php echo $this->NetCommonsForm->hidden('ContentComment.content_key', array('value' => $contentKey)); ?>
														<?php echo $this->NetCommonsForm->hidden('_tmp.redirect_url', array('value' => $redirectUrl)); ?>
														<?php echo $this->NetCommonsForm->hidden('_tmp.process', array('value' => ContentCommentsComponent::PROCESS_EDIT)); ?>
														<?php echo $this->NetCommonsForm->hidden('Block.id', array('value' => Current::read('Block.id'))); ?>

														<div class="form-group">
															<div class="input textarea">
																<?php
																$contentCommentComment = array(
																	'class' => 'form-control nc-noresize',
																	'rows' => 2,
																	'value' => nl2br($contentComment['ContentComment']['comment']),
																);

																/* 編集時入力エラー対応 編集処理で、idが同じのみvalueをセットしない */
																if ($this->request->data('_tmp.process') == ContentCommentsComponent::PROCESS_EDIT &&
																	$this->request->data('_tmp.ContentComment.id') == $contentComment['ContentComment']['id']) {
																	$contentCommentComment['value'] = '';
																}

																echo $this->NetCommonsForm->textarea('ContentComment.comment', $contentCommentComment);
																?>
															</div>
														</div>

														<?php /* 編集時入力エラー対応 編集処理で、idが同じのみエラー表示エリア配置 */ ?>
														<?php if ($this->request->data('_tmp.process') == ContentCommentsComponent::PROCESS_EDIT &&
															$this->request->data('_tmp.ContentComment.id') == $contentComment['ContentComment']['id']): ?>
															<div class="has-error">
																<?php echo $this->NetCommonsForm->error('ContentComment.comment', null, array('class' => 'help-block')); ?>
															</div>
														<?php endif ?>

														<div class="row">
															<div class="col-xs-12 text-center">
																<button type="button" class="btn btn-default btn-sm" ng-click="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?> = false;">
																	<?php echo __d('net_commons', 'Cancel') ?>
																</button>
																<?php echo $this->NetCommonsForm->button(
																	__d('content_comments', 'Comment'),
																	array(
																		'class' => 'btn btn-success btn-sm',
																)); ?>
															</div>
														</div>
													<?php echo $this->NetCommonsForm->end(); ?>
												</div>
											<?php endif; ?>

										</div>
									</div>
									<div class="text-right" ng-hide="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?>">
										<?php /* 承認許可あり and 未承認のコメント  */ ?>
										<?php if (Current::permission('content_comment_publishable') && $contentComment['ContentComment']['status'] == ContentComment::STATUS_APPROVED): ?>
											<?php /* 承認 */ ?>
											<?php echo $this->NetCommonsForm->create('ContentComment', array(
												'name' => 'form',
												'style' => 'display: inline;',
												'url' => '/content_comments/content_comments/edit/' . Current::read('Frame.id'),
												'type' => 'put',
											)); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.id', array('value' => $contentComment['ContentComment']['id'])); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.plugin_key', array('value' => $pluginKey)); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.content_key', array('value' => $contentKey)); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.status', array('value' => ContentComment::STATUS_PUBLISHED)); //公開 ?>
												<?php echo $this->NetCommonsForm->hidden('_tmp.redirect_url', array('value' => $redirectUrl)); ?>
												<?php echo $this->NetCommonsForm->hidden('_tmp.process', array('value' => ContentCommentsComponent::PROCESS_APPROVED)); ?>
												<?php echo $this->NetCommonsForm->hidden('Block.id', array('value' => Current::read('Block.id'))); ?>

												<?php echo $this->NetCommonsForm->button(
													"<span class='glyphicon glyphicon-ok'></span>",
													array(
														'class' => 'btn btn-warning btn-sm',
														'onclick' => 'return confirm(\'' . sprintf(__d('content_comments', 'Approving the %s. Are you sure to proceed?'), __d('content_comments', 'comment')) . '\')'
												)); ?>
											<?php echo $this->NetCommonsForm->end(); ?>
										<?php endif; ?>

										<?php /* 編集許可あり or 自分で投稿したコメントなら、編集・削除可能 */ ?>
										<?php if (Current::permission('content_comment_editable') || $contentComment['ContentComment']['created_user'] == (int)AuthComponent::user('id')): ?>
											<?php /* 編集 */ ?>
											<?php /* 編集の表示・非表示フラグ 非表示 */ ?>
											<input class="hide" type="checkbox" ng-model="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?>"
												<?php /* 編集時入力エラー対応　編集処理で、idが同じなら編集画面を開く */ ?>
												<?php if ($this->request->data('_tmp.process') == ContentCommentsComponent::PROCESS_EDIT &&
													$this->request->data('_tmp.ContentComment.id') == $contentComment['ContentComment']['id']): ?>
													ng-init="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?> = true;"
												<?php endif; ?>>
											<button type="button" class="btn btn-primary btn-sm" ng-click="isDisplayEdit<?php echo $contentComment['ContentComment']['id']; ?> = true;">
												<span class='glyphicon glyphicon-edit'></span>
											</button>

											<?php /* 削除 */ ?>
											<?php echo $this->NetCommonsForm->create('ContentComment', array(
												'name' => 'form',
												'style' => 'display: inline;',
												'url' => '/content_comments/content_comments/edit/' . Current::read('Frame.id'),
												'type' => 'delete',
											)); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.id', array('value' => $contentComment['ContentComment']['id'])); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.created_user', array('value' => $contentComment['ContentComment']['created_user'])); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.plugin_key', array('value' => $pluginKey)); ?>
												<?php echo $this->NetCommonsForm->hidden('ContentComment.content_key', array('value' => $contentKey)); ?>
												<?php echo $this->NetCommonsForm->hidden('_tmp.redirect_url', array('value' => $redirectUrl)); ?>
												<?php echo $this->NetCommonsForm->hidden('_tmp.process', array('value' => ContentCommentsComponent::PROCESS_DELETE)); ?>
												<?php echo $this->NetCommonsForm->hidden('Block.id', array('value' => Current::read('Block.id'))); ?>

												<?php echo $this->NetCommonsForm->button(
													"<span class='glyphicon glyphicon-trash'></span>",
													array(
														'class' => 'btn btn-danger btn-sm',
														'onclick' => 'return confirm(\'' . sprintf(__d('net_commons', 'Deleting the %s. Are you sure to proceed?'), __d('content_comments', 'comment')) . '\')'
												)); ?>
											<?php echo $this->NetCommonsForm->end(); ?>
										<?php endif; ?>
									</div>
								</article>
								<?php $i++; ?>
							<?php endforeach ?>

							<?php /* もっと見る */ ?>
							<div class="comment-more">
								<button type="button" class="btn btn-info btn-block more <?php echo $i <= ContentCommentsComponent::START_LIMIT ? 'hidden' : '' ?>"
										ng-click="more();">
									<?php echo h(__d('net_commons', 'More')); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</article>
		</div>
		</div>
<?php endif;
