<?php

if (cfr('ACL')) {
    
} else {
    show_error(__('Access denied'));
}
