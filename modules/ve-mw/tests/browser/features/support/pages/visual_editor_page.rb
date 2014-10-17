class VisualEditorPage
  include PageObject

  include URL
  page_url URL.url("User:#{ENV['MEDIAWIKI_USER']}/#{ENV['BROWSER']}?vehidebetadialog=true&veaction=edit")
  span(:bullet_number_selector, class: "oo-ui-iconElement-icon oo-ui-icon-bullet-list")
  span(:cite_button, text: "Cite")
  div(:cite_select, css: "div.oo-ui-widget:nth-child(5) > div:nth-child(2)")
  a(:cite_book, css: ".oo-ui-tool-name-cite-book > a:nth-child(1)")
  a(:cite_journal, css: ".oo-ui-tool-name-cite-journal > a:nth-child(1)")
  a(:cite_news, css: ".oo-ui-tool-name-cite-news > a:nth-child(1)")
  span(:cite_website, css: ".oo-ui-icon-ref-cite-web")
  div(:container_disabled, class: "oo-ui-widget oo-ui-widget-disabled oo-ui-flaggedElement-constructive oo-ui-.oo-ui-buttonElement-framed")
  div(:content, class: "ve-ce-branchNode")
  span(:decrease_indentation, class: "oo-ui-iconElement-icon oo-ui-icon-outdent-list")
  span(:downarrow, class: "oo-ui-indicatorElement-indicator oo-ui-indicator-down")
  a(:edit_ve, title: /Edit this page with VisualEditor/)
  a(:edit_wikitext, title: /You can edit this page\./)
  a(:heading, text: /Heading/)
  span(:hamburger_menu, css: "div.oo-ui-listToolGroup:nth-child(2) > span:nth-child(1) > span:nth-child(3)")
  span(:increase_indentation, class: "oo-ui-iconElement-icon oo-ui-icon-indent-list")
  span(:insert_menu, class: "oo-ui-popupToolGroup-handle", index: 4)
  div(:insert_references, class: "oo-ui-processDialog-location")
  div(:ip_warning, class: "ve-ui-mwNoticesPopupTool-item")
  span(:looks_good, class: "oo-ui-labelElement-label", text: "Looks good to me")
  div(:medium_dialog, class: "oo-ui-window oo-ui-dialog oo-ui-dialog-open oo-ui-dialog-medium")
  span(:options_in_hamburger, class: "oo-ui-tool-title", text: "Options")
  div(:page_text, id: "mw-content-text")
  a(:page_title, text: /Page title/)
  a(:paragraph, text: /Paragraph/)
  a(:preformatted, text: /Preformatted/)
  span(:refs_link, text: "Reference")
  div(:save_disabled, class: "oo-ui-widget oo-ui-widget-disabled oo-ui-flaggedElement-constructive oo-ui-.oo-ui-buttonElement-framed")
  a(:save_page, css: "div.ve-init-mw-viewPageTarget-toolbar-actions > div.oo-ui-flaggedElement-constructive > a")
  div(:save_enabled, css: "div.ve-init-mw-viewPageTarget-toolbar-actions > div.oo-ui-flaggedElement-constructive.oo-ui-widget-enabled")
  a(:subheading1, text: /Sub-heading 1/)
  a(:subheading2, text: /Sub-heading 2/)
  a(:subheading3, text: /Sub-heading 3/)
  a(:subheading4, text: /Sub-heading 4/)
  span(:switch_to_source_editing, class: "oo-ui-iconElement-icon oo-ui-icon-source")
  div(:heading_dropdown_menus, class: "oo-ui-toolGroup-tools oo-ui-clippableElement-clippable")
  div(:formatting_option_menus, class: "oo-ui-toolGroup-tools oo-ui-clippableElement-clippable", index: 1)
  span(:page_settings, class: "oo-ui-iconElement-icon oo-ui-icon-settings")
  div(:indentation_pull_down, class: "oo-ui-toolGroup-tools oo-ui-clippableElement-clippable", index: 3)
  div(:insert_pull_down, class: "oo-ui-toolGroup-tools oo-ui-clippableElement-clippable", index: 4)
  div(:page_option_menu, class: "oo-ui-toolGroup-tools oo-ui-clippableElement-clippable", index: 5)
  span(:special_character, class: "oo-ui-iconElement-icon oo-ui-icon-special-character")
  div(:iframe, class: "oo-ui-window-frame")
  figure(:media_image, index: 0)
  unordered_list(:media_caption, class: "ve-ui-contextMenuWidget")
  span(:cite_menu, class: "oo-ui-popupToolGroup-handle", index: 2)
  div(:cite_pull_down, class: "oo-ui-toolGroup-tools oo-ui-clippableElement-clippable", index: 2)
  div(:right_navigation, id: "p-views")
  div(:left_navigation, id: "left-navigation")
  div(:toolbar, class: "ve-init-mw-viewPageTarget-toolbar")
  span(:category_link, class: "oo-ui-iconElement-icon oo-ui-icon-tag")
  a(:formula_link, css: "span.oo-ui-tool-name-math > a.oo-ui-tool-link")
  img(:formula_image, class: "mwe-math-fallback-png-inline")

  if ENV["BROWSER"] == "chrome"
    div(:tools_menu, class: "oo-ui-widget oo-ui-widget-enabled oo-ui-toolGroup oo-ui-iconElement oo-ui-popupToolGroup oo-ui-listToolGroup")
  else
    span(:tools_menu, class: "oo-ui-iconElement-icon oo-ui-icon-menu")
  end

  span(:ve_bold_text, class: "oo-ui-iconElement-icon oo-ui-icon-bold-b")
  span(:ve_bullets, class: "oo-ui-iconElement-icon oo-ui-icon-bullet-list", index: 1)
  span(:ve_computer_code, class: "oo-ui-iconElement-icon oo-ui-icon-code")
  div(:ve_heading_menu, class: "oo-ui-iconElement-icon oo-ui-icon-down")
  span(:ve_link_icon, class: "oo-ui-iconElement-icon oo-ui-icon-link")
  span(:ve_italics, class: "oo-ui-iconElement-icon oo-ui-icon-italic-i")
  span(:ve_media_menu, class: "oo-ui-iconElement-icon oo-ui-icon-picture")
  span(:ve_references, class: "oo-ui-iconElement-icon oo-ui-icon-references")
  span(:ve_numbering, class: "oo-ui-iconElement-icon oo-ui-icon-number-list")
  span(:ve_strikethrough, class: "oo-ui-iconElement-icon oo-ui-icon-strikethrough-s")
  span(:ve_subscript, class: "oo-ui-iconElement-icon oo-ui-icon-subscript")
  span(:ve_superscript, class: "oo-ui-iconElement-icon oo-ui-icon-superscript")
  span(:ve_text_style, class: "oo-ui-iconElement-icon oo-ui-icon-text-style")
  span(:ve_underline, class: "oo-ui-iconElement-icon oo-ui-icon-underline-u")
  div(:visual_editor_toolbar, class: "oo-ui-toolbar-tools")
  a(:transclusion, css: "span.oo-ui-widget.oo-ui-iconElement.oo-ui-tool.oo-ui-tool-name-transclusion.oo-ui-widget-enabled > a")
  text_area(:wikitext_editor, id: "wpTextbox1")
  a(:first_reference, text: "[1]", index: 1)
  a(:second_reference, text: "[1]", index: 2)
  unordered_list(:link_list, class: 'oo-ui-widget oo-ui-widget-enabled oo-ui-selectWidget oo-ui-selectWidget-depressed oo-ui-clippableElement-clippable oo-ui-menuWidget oo-ui-textInputMenuWidget oo-ui-lookupWidget-menu ve-ui-mwLinkTargetInputWidget-menu')
  a(:new_link, class: "ve-ce-linkAnnotation ve-ce-mwInternalLinkAnnotation new")
  a(:internal_link, class: "ve-ce-linkAnnotation ve-ce-mwInternalLinkAnnotation")
  unordered_list(:popup_icon, class: "ve-ui-contextMenuWidget")
  span(:basic_reference, class: "oo-ui-iconElement-icon oo-ui-icon-reference")
  div(:toolbar_actions, class: "oo-ui-toolbar-actions")
  span(:media_insert_menu, class: "oo-ui-tool-name-media")
  span(:template_insert_menu, class: "oo-ui-tool-name-transclusion")
  span(:ref_list_insert_menu, class: "oo-ui-tool-name-referencesList")
  span(:formula_insert_menu,class: "oo-ui-tool-name-math")
  div(:language_notification, class: "tipsy-inner")

  in_iframe(index: 0) do |frame|
    a(:beta_warning, title: "Close", frame: frame)
    a(:cite_add_more_information_button, css: ".ve-ui-mwParameterPage-more a", index: 7, frame: frame)
    a(:book_add_more_information_button, css: ".ve-ui-mwParameterPage-more a", index: 7, frame: frame)
    #text_field(:cite_custom_field_name, css: ".oo-ui-textInputWidget-decorated > input:nth-child(1)", frame: frame)
    text_field(:cite_custom_field_name, css: ".oo-ui-searchWidget-query > div:nth-child(1) > input:nth-child(1)", frame: frame)
    text_area(:cite_new_website_field, css: "div.oo-ui-layout:nth-child(10) > div:nth-child(3) > div:nth-child(1) > textarea:nth-child(1)", frame: frame)
    div(:cite_new_field_label, css: ".oo-ui-optionWidget", frame: frame)
    div(:cite_show_more_fields, class: "ve-ui-mwMoreParametersResultWidget-label", frame: frame)
    div(:cite_ui, class: "oo-ui-window-body", frame: frame)

    text_area(:cite_first_textarea, index: 0, frame: frame)
    text_area(:cite_second_textarea, index: 1, frame: frame)
    text_area(:cite_third_textarea, index: 2, frame: frame)
    text_area(:cite_fourth_textarea, index: 3, frame: frame)
    text_area(:cite_fifth_textarea, index: 4, frame: frame)
    text_area(:cite_sixth_textarea, index: 5, frame: frame)
    text_area(:cite_seventh_textarea, index: 6, frame: frame)
    text_area(:cite_eighth_textarea, index: 7, frame: frame)

    div(:content_box, class: "ve-ce-documentNode ve-ce-branchNode", frame: frame)
    span(:insert_citation, text: "Insert", frame: frame)
    span(:links_done, text: "Done", frame: frame)
    text_field(:link_textfield, index: 0, frame: frame)
    span(:another_save_page, class: "oo-ui-labelElement-label", text: "Save page", frame: frame)
    div(:suggestion_list, class: "ve-ui-mwTitleInputWidget-menu")
    div(:options_page_title, class: "oo-ui-processDialog-location", text: "Options", frame: frame)
    div(:ve_link_ui, class: "oo-ui-window-head", frame: frame)
  end

  # not having beta warning makes iframes off by one
  in_iframe(index: 0) do |frame|
    span(:add_template, text: "Add template", frame: frame)
    span(:insert_template, text: "Insert", frame: frame)
    span(:confirm_switch, text: "Keep changes", frame: frame)
    span(:confirm_switch_cancel, text: "Resume editing", frame: frame)
    span(:confirm_switch_cancel_on_switch, text: "Cancel", frame: frame)
    span(:confirm_switch_discard, text: "Discard changes", frame: frame)
    div(:content_box, class: "ve-ce-documentNode ve-ce-branchNode", frame: frame)
    text_area(:describe_change, index: 0, frame: frame)
    div(:diff_view, class: "ve-ui-mwSaveDialog-viewer", frame: frame)
    span(:ex, text: "Return to save form", frame: frame)
    span(:insert_references_list, text: "Insert", frame: frame)
    span(:media_apply_changes, text: "Insert", frame: frame)
    text_field(:media_search, css: "div.oo-ui-textInputWidget > input", frame: frame)
    div(:media_select, class: "ve-ui-mwMediaResultWidget-overlay", frame: frame)
    checkbox(:minor_edit, id: "wpMinoredit", frame: frame)
    text_field(:parameter_box, index: 0, frame: frame)
    div(:parameter_icon, text: "q", frame: frame)
    a(:remove_parameter, css: ".ve-ui-mwParameterPage-removeButton > a:nth-child(1)", frame: frame)
    a(:remove_template, title: "Remove template", frame: frame)
    span(:return_to_save, class: "oo-ui-labelElement-label", text: "Return to save form", frame: frame)
    span(:review_changes, class: "oo-ui-labelElement-label", text: "Review your changes", frame: frame)
    div(:review_failed, class: "oo-ui-window-head", frame: frame)
    span(:second_save_page, class: "oo-ui-labelElement-label", text: "Save page", frame: frame)
    div(:template_header, class: "ve-ui-mwTransclusionDialog-single", frame: frame)
    li(:template_list_item, text: "S", frame: frame)
    div(:title, class: "oo-ui-processDialog-location", frame: frame)
    text_area(:transclusion_textarea, index: 0, frame: frame)
    text_field(:transclusion_textfield, index: 0, frame: frame)
    span(:existing_reference, text: "Use an existing reference", frame: frame)
    div(:extension_reference, class: "ve-ui-mwReferenceResultWidget-shield", frame: frame)
    span(:page_settings_heading, css: "div.oo-ui-fieldsetLayout > span.oo-ui-labelElement-label", frame: frame)
    div(:redirect_page_option, class: "oo-ui-layout oo-ui-labelElement oo-ui-fieldLayout oo-ui-fieldLayout-align-inline", frame: frame)
    div(:target_redirect, class: "oo-ui-layout oo-ui-fieldLayout oo-ui-fieldLayout-align-top oo-ui-fieldLayout-disabled", frame: frame)
    div(:prevent_redirect, class: "oo-ui-layout oo-ui-labelElement oo-ui-fieldLayout oo-ui-fieldLayout-align-inline oo-ui-fieldLayout-disabled", frame: frame)
    div(:table_of_contents, class: "oo-ui-layout oo-ui-labelElement oo-ui-fieldLayout oo-ui-fieldLayout-align-top", frame: frame)
    div(:page_settings_editlinks, class: "oo-ui-layout oo-ui-labelElement oo-ui-fieldLayout oo-ui-fieldLayout-align-inline", frame: frame)
    text_field(:media_alternative_text, css: "div.oo-ui-widget.oo-ui-widget-enabled.oo-ui-inputWidget.oo-ui-textInputWidget.ve-ui-mwMediaDialog-altText > input", frame: frame)
    div(:media_alternative_block, class: "oo-ui-layout oo-ui-iconElement oo-ui-labelElement oo-ui-fieldsetLayout", index: 1, frame: frame)
    list_item(:media_advanced_settings, class: "oo-ui-widget oo-ui-widget-enabled oo-ui-optionWidget oo-ui-decoratedOptionWidget oo-ui-outlineItemWidget oo-ui-outlineItemWidget-level-0 oo-ui-iconElement oo-ui-labelElement", frame: frame)
    a(:insert_media, css: "div.oo-ui-processDialog-actions-primary > div.oo-ui-buttonElement-frameless > a", frame: frame)
    text_area(:formula_area, class: "oo-ui-ltr", frame: frame)
    div(:settings_apply_button, class: "oo-ui-processDialog-actions-primary", frame: frame)
  end

  # not having beta warning makes iframes off by one
  in_iframe(index: 1) do |frame|
    div(:links_diff_view, class: "ve-ui-mwSaveDialog-viewer", frame: frame)
    span(:links_review_changes, class: "oo-ui-labelElement-label", text: "Review your changes", frame: frame)
    div(:media_diff_view, class: "ve-ui-mwSaveDialog-viewer", frame: frame)
    span(:media_exit, text: "Return to save form", frame: frame)
  end
end
