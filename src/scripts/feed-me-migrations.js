
$('.settings-pane-wrap').each(function () {
    const $feed = $(this);
    const $btnGroup = $feed.find('.btngroup');
    const $lastBtn = $btnGroup.find('a:last-child');
    const $newBtn = $lastBtn.clone();

    $newBtn.find('.btn-text').text('Create Migration');
    $newBtn.find('.fa').attr('class', 'fa fa-arrow-circle-up');

    const url = new URL($newBtn.attr('href'));
    const feedId = url.searchParams.get('feedId');

    url.search = 'p=cp/actions/feed-me-migrations/migrations/create&feedId=' + feedId;

    $newBtn.attr('href', url.toString());

    $btnGroup.append($newBtn);
});

