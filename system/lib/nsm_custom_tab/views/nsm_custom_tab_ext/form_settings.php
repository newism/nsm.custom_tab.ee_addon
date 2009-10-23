<div class="tg">
	<h2><?php echo str_replace("{addon_name}", $this->name, $LANG->line("extension_access_title")); ?></h2>
	<table>
		<tbody>
			<tr class="even">
				<th>
					<?php echo str_replace("{addon_name}",  $this->addon_name, $LANG->line("enable_extension_label")); ?>
				</th>
				<td>
					<?php echo $this->select_box($this->site_settings["enabled"], array(1 => "yes", 0 => "no"), "Nsm_custom_tab_ext[enabled]"); ?>
				</td>
			</tr>
			<tr class="odd">
				<th>
					<?php echo $LANG->line('which_groups_label'); ?>
				</th>
				<td>
					<?php
						foreach($member_group_query->result as $member_group) :
						$checked = in_array($member_group['group_id'], $this->site_settings['member_groups']) ? "checked='checked'" : "";
					?>
						<label class="checkbox">
							<input
								<?php echo $checked ?>
								type="checkbox"
								name="Nsm_custom_tab_ext[member_groups][]"
								value="<?php echo $member_group['group_id'] ?>"
							/>
							<?php echo $member_group['group_title'] ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr class="even">
				<th>
					<?php echo $LANG->line('which_weblogs_label'); ?>
				</th>
				<td>
					<?php
						foreach($weblog_query->result as $weblog) :
						$checked = in_array($weblog['weblog_id'], $this->site_settings['weblogs']) ? "checked='checked'" : "";
					?>
						<label class="checkbox">
							<input
								<?php echo $checked ?>
								type="checkbox"
								name="Nsm_custom_tab_ext[weblogs][]"
								value="<?php echo $weblog['weblog_id'] ?>"
							/>
							<?php echo $weblog['blog_title'] ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div class="tg">
	<h2><?php print $LANG->line("check_for_updates_title") ?></h2>
	<div class="info"><?= str_replace("{addon_name}", $this->addon_name, $LANG->line("check_for_updates_info")); ?></div>
	<table>
		<tbody>
			<tr class="odd">
				<th><?= $LANG->line("check_for_updates_label") ?></th>
				<td>
					<select<?php if(!$lgau_enabled) : ?> disabled="disabled"<?php endif; ?> name="Nsm_custom_tab_ext[check_for_updates]">
						<option value="1"<?= ($this->site_settings["check_for_updates"] == TRUE && $lgau_enabled === TRUE) ? 'selected="selected"' : ''; ?>>
							<?= $LANG->line("yes") ?>
						</option>
						<option value="0"<?= ($this->site_settings["check_for_updates"] == FALSE || $lgau_enabled === FALSE) ? 'selected="selected"' : ''; ?>>
							<?= $LANG->line("no") ?>
						</option>
					</select>
					<?php if(!$lgau_enabled) : ?>
						&nbsp;
						<span class='highlight'>LG Addon Updater is not installed and activated.</span>
						<input type="hidden" name="check_for_updates" value="0" />
					<? endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>