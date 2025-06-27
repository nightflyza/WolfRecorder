[live_wall_feature]
OPTION=LIVE_WALL
LABEL="Live wall feature"
TYPE="CHECKBOX"
DEFAULT="1"

[motion_post_detection]
OPTION=MODET_ENABLED
LABEL="Motion detection and filtering feature"
TYPE="CHECKBOX"
DEFAULT="1"

[quick_search_feature]
OPTION=QUICKSEARCH_ENABLED
LABEL="Quick camera search feature"
TYPE="CHECKBOX"
DEFAULT="1"

[export_forced_schedule]
OPTION=EXPORT_FORCED_SCHED
LABEL="Forced schedule on video export"
TYPE="CHECKBOX"
DEFAULT="1"

[recorder_on_camera_activation]
OPTION=RECORDER_ON_CAMERA_ACTIVATION
LABEL="Run recording when camera is activated"
TYPE="CHECKBOX"
DEFAULT="1"

[channel_shots_validation]
OPTION=CHANSHOTS_VALIDATION
LABEL="Channel shots validation"
TYPE="CHECKBOX"
DEFAULT="1"

[page_load_indicator]
OPTION="PAGE_LOAD_INDICATOR"
LABEL="Page load indicator"
TYPE="CHECKBOX"
DEFAULT="1"

[channel_shots_embed]
OPTION=CHANSHOTS_EMBED
LABEL="Embed channel screenshots into page body"
TYPE="CHECKBOX"
DEFAULT="0"

[recorder_debug_log]
OPTION=RECORDER_DEBUG
LABEL="Write recorder debug"
TYPE="CHECKBOX"
DEFAULT="0"

[rotator_debug_log]
OPTION=ROTATOR_DEBUG
LABEL="Write rotator debug log"
TYPE="CHECKBOX"
DEFAULT="0"

[licenses]
OPTION=LICENSES_ENABLED
LABEL="License keys module enabled"
TYPE="CHECKBOX"
DEFAULT="0"

[stardustflock]
OPTION=STARDUST_FLOCK_FORCE
LABEL="StarDust flock mode"
TYPE="CHECKBOX"
DEFAULT="0"

[backups_age]
OPTION=BACKUPS_MAX_AGE
LABEL="DB backups max age in days"
TYPE="SELECT"
VALUES="1,3,5,7,9"
DEFAULT="7"
VALIDATOR="is_numeric"
ONINVALID="Wrong days count"

[pwa_name]
OPTION="WA_NAME"
LABEL="PWA short name"
TYPE="TEXT"
DEFAULT="WolfRecorder"
SAVEFILTER="safe"

[pwa_icon_192]
OPTION="WA_ICON_192"
LABEL="PWA icon 192x192"
TYPE="TEXT"
DEFAULT="skins/webapp/wa192.png"
PATTERN="pathorurl"
VALIDATOR="isPwaIconAcceptable192"
ONINVALID="Icon URL is invalid or not contain valid PNG image 192x192"

[pwa_icon_512]
OPTION="WA_ICON_512"
LABEL="PWA icon 512x512"
TYPE="TEXT"
DEFAULT="skins/webapp/wa512.png"
PATTERN="pathorurl"
VALIDATOR="isPwaIconAcceptable512"
ONINVALID="Icon URL is invalid or not contain valid PNG image 512x512"

[pwa_display]
OPTION=WA_DISPLAY
LABEL="PWA display mode"
TYPE="SELECT"
VALUES="standalone,fullscreen"
DEFAULT="standalone"
VALIDATOR="is_string"
SAVEFILTER="safe"
ONINVALID="Wrong display mode"

[exports_reserve]
OPTION="EXPORTS_RESERVED_SPACE"
LABEL="Space % reserved for exported users videos"
TYPE="SLIDER"
DEFAULT="10"
VALUES="5..85"
VALIDATOR="is_numeric"
ONINVALID="Wrong allocated space percent value"