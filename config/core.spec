[default_locale]
OPTION=YALF_LANG
LABEL="Default locale"
TYPE="SELECT"
VALUES="english,ukrainian,romanian,russian"
DEFAULT="english"


[locale_switchable]
OPTION=YALF_LANG_SWITCHABLE
LABEL="Allow locale switch"
TYPE="CHECKBOX"
DEFAULT="1"
VALIDATOR="is_numeric"
ONINVALID=""



