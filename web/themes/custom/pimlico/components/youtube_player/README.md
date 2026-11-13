# YouTube player

A YouTube video in the site's style. The poster (the video's thumbnail,
stored by Drupal's oEmbed) and the 88px play control show first; nothing
loads from YouTube, and no YouTube cookies are set, until someone presses
play. The video then plays in Plyr, with the same controls as uploaded
films, from youtube-nocookie.com.

Editors add a YouTube link as a "Remote video" in the Media library and use
it in a Video section. `templates/media/media--remote-video.html.twig`
renders YouTube links with this component; Vimeo links keep the Plyr
formatter. The video ID and any start time (`t=` or `start=`) come from
`includes/media.inc`.
