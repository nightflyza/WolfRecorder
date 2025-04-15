[default_locale]
OPTION=YALF_LANG
LABEL="Default locale"
TYPE="SELECT"
VALUES="english,ukrainian,romanian,russian"
DEFAULT="english"
VALIDATOR="isLocaleExists"
ONINVALID="Locale not exists"

[locale_switchable]
OPTION=YALF_LANG_SWITCHABLE
LABEL="Allow locale switch"
TYPE="CHECKBOX"
DEFAULT="1"
VALIDATOR="is_numeric"

[authkeepdefault]
OPTION="YALF_AUTH_KEEP_DEFAULT"
LABEL="Keep user logged in by default"
TYPE="CHECKBOX"
DEFAULT="1"
VALIDATOR="is_numeric"

[authkeepcbflag]
OPTION="YALF_AUTH_KEEP_CB"
LABEL="Show stay logged in checkbox in login form"
TYPE="CHECKBOX"
DEFAULT="0"
VALIDATOR="is_numeric"

[authoknoredir]
OPTION="YALF_AUTH_NOREDIR"
LABEL="Do not redirect after logging in"
TYPE="CHECKBOX"
DEFAULT="0"
VALIDATOR="is_numeric"

[bnranding_url]
OPTION="YALF_URL"
LABEL="Service URL"
TYPE="TEXT"
PATTERN="url"
DEFAULT="https://wolfrecorder.com"
VALIDATOR="isUrlValid"
ONINVALID="Service URL is invalid"

[branding_name]
OPTION="YALF_APP"
LABEL="Service name"
TYPE="text"
DEFAULT="WolfRecorder"
SAVEFILTER="safe"

[branding_title]
OPTION="YALF_TITLE"
LABEL="App title"
TYPE="text"
DEFAULT="WolfRecorder"
SAVEFILTER="safe"

[branding_logo]
OPTION="YALF_LOGO"
LABEL="Service logo"
TYPE="TEXT"
PATTERN="pathorurl"
DEFAULT="skins/wrcolor.png"
VALIDATOR="isLogoAcceptable"
ONINVALID="Logo URL is invalid or not contain valid image"
