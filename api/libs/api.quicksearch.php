<?php


/**
 * Renders the search form and frontend controller for live cameras list
 *
 * @return string
 */
function wr_QuickSearchRenderForm() {
    global $ubillingConfig;
    $modList = array('livecams', 'archive', 'export');
    $denyList = array('livechannel', 'viewchannel', 'exportchannel');
    $result = '';

    if ($ubillingConfig->getAlterParam('QUICKSEARCH_ENABLED')) {
        if (ubRouting::checkGet('module')) {
            $modList = array_flip($modList);
            $curModule = ubRouting::get('module', 'gigasafe');
            $skipRenderFlag = (ubRouting::checkGet($denyList, true, true)) ? true : false;
            if (isset($modList[$curModule]) and !$skipRenderFlag) {
                $result .= wf_tag('div', false, 'searchform');
                $result .= wf_TextInput('camsearch', ' ' . '', '', false, 15, '', '', 'camsearch', 'placeholder="' . __('Quick search') . '...' . '"');

                $result .= wf_tag('button', false, 'clear-btn', 'type="button" aria-label="Clear search"') . '&times;' . wf_tag('button', true);
                $result .= wf_tag('div', true);

                $result .= wf_tag('script');
                $result .= "
                    document.getElementById('camsearch').addEventListener('input', function () {
                        const searchValue = this.value.toLowerCase();
                        const cameras = document.querySelectorAll('[id^=\"wrcamcont_\"]');
                        const statusContainer = document.getElementById('wrqsstatus');
                        let visibleCount = 0;
                
                        cameras.forEach(camera => {
                            const idText = camera.id.toLowerCase();
                            if (searchValue === '' || idText.includes(searchValue)) {
                                camera.classList.remove('hiddencam');
                                camera.style.display = 'block';
                                requestAnimationFrame(() => camera.style.opacity = '1');
                                visibleCount++;
                            } else {
                                camera.classList.add('hiddencam');
                                setTimeout(() => {
                                    if (camera.classList.contains('hiddencam')) {
                                        camera.style.display = 'none';
                                    }
                                }, 300);
                            }
                        });
                
                        //no cameras found
                        if (visibleCount === 0) {
                            statusContainer.textContent = '" . __('Nothing found') . "';
                        } else {
                            statusContainer.textContent = '';
                        }
                    });

                    document.addEventListener('DOMContentLoaded', () => {
                        const searchInput = document.getElementById('camsearch');
                        const clearButton = document.querySelector('.clear-btn');
                        searchInput.addEventListener('input', () => {
                            if (searchInput.value.trim() !== '') {
                                clearButton.style.display = 'flex';
                            } else {
                                clearButton.style.display = 'none';
                            }
                        });

                        clearButton.addEventListener('click', () => {
                            searchInput.value = '';
                            clearButton.style.display = 'none';
                            searchInput.dispatchEvent(new Event('input'));
                            searchInput.focus();
                        });
                    });
                ";
                $result .= wf_tag('script', true);
            }
        }
    }

    return ($result);
}
