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

[backups_age]
OPTION=BACKUPS_MAX_AGE
LABEL="DB backups max age in days"
TYPE="SELECT"
VALUES="1,3,5,7,9"
DEFAULT="7"
VALIDATOR="is_numeric"
ONINVALID="Wrong days count"