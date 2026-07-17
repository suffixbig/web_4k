const skillPlatforms = JSON.parse(document.getElementById('skillPlatformData').textContent);
let platform = 'codex';

function getInstallPrompt(skill) {
  const action = platform === 'codex' ? '下載並安裝' : '下載並依此建立 Claude Code 本機技能';
  return `請${action} ${skill.url}（帥龍萌姬桌布館 ${skill.version}）。依 SKILL.md 處理「換桌布」、帶條件換桌布，以及搜尋桌布／桌布搜尋／搜尋壁紙／壁紙搜尋；驗證 HTTPS download_url、套用本機 Windows 桌布並回寫 current_set。`;
}

/* 顯示操作提示。 */
function showToast(message) { const $toast = $('#toast'); $toast.text(message).addClass('show'); clearTimeout(showToast.timer); showToast.timer = setTimeout(() => $toast.removeClass('show'), 2500); }
/* 複製安裝提示文字。 */
async function copyPrompt(text) { try { await navigator.clipboard.writeText(text); showToast('安裝指令已複製。'); } catch { showToast('無法存取剪貼簿，請手動複製。'); } }
/* 更新所選 AI 平台的安裝文字。 */
function setPlatform(nextPlatform) {
  if (!skillPlatforms[nextPlatform]) return;
  platform = nextPlatform;
  const skill = skillPlatforms[platform];
  const prompt = getInstallPrompt(skill);
  const $tabs = $('[data-platform]');
  $tabs.each(function () {
    const selected = $(this).data('platform') === platform;
    $(this).toggleClass('active', selected).attr({ 'aria-selected': selected, tabindex: selected ? 0 : -1 });
  });
  $('#skillPlatformPanel').attr('aria-labelledby', platform + 'Tab');
  $('#platformLabel').text(platform.toUpperCase() + ' INSTALL PROMPT');
  $('#installPrompt').text(prompt);
  $('#copyInstall').html('<i class="fa-solid fa-copy" aria-hidden="true"></i>複製 ' + skill.name + ' 安裝指令');
  $('.skill-markdown-download').attr('href', skill.path).attr('download', 'SKILL.md');
  $('.install-command .skill-markdown-download').html('<i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>下載 ' + skill.name + ' SKILL.md');
  $('#previewDownload').text('下載 ' + skill.name + ' SKILL.md');
  $('#skillFileTitle').text(skill.name + ' 技能規格');
  $('#skillVersion').text('版本 ' + skill.version);
  $('#skillUpdatedAt').text('更新日期 ' + skill.updatedAt);
  $('#skillPreviewDescription').text('目前預覽 ' + skill.name + ' 專用檔案；切換平台後，這裡會同步顯示對應的 SKILL.md。');
  $('#skillMarkdownPreview').text(skill.markdown);
}
$(function () {
  $(document).on('click', '[data-platform]', function () { setPlatform($(this).data('platform')); })
    .on('keydown', '[role="tab"]', function (event) {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const keys = Object.keys(skillPlatforms);
      const currentIndex = keys.indexOf(platform);
      const nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? keys.length - 1 : (currentIndex + (event.key === 'ArrowRight' ? 1 : -1) + keys.length) % keys.length;
      setPlatform(keys[nextIndex]);
      $('#' + keys[nextIndex] + 'Tab').trigger('focus');
    })
    .on('click', '[data-command]', function () { copyPrompt($(this).data('command')); });
  $('#copyInstall').on('click', () => copyPrompt(getInstallPrompt(skillPlatforms[platform])));
  setPlatform(platform);
});
