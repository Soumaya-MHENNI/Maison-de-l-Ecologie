<?php
/*  label - the field name that will be displayed
    input - the input type (e.g text, select, radio, ...)
    helps - information to help the user filling in the field
    application - which attchment mime type to apply
    exclusions - which attchment mime type to exclude
    required - is the field required? (default false)
    error_text - optional field to describe the error (if required is set to true)
    options - optional field for radio and select types
    show_in_modal - whether to show the field in modal or not (default true)
    show_in_edit - whether to show the field in classic edit view or not (default true)
    extra_rows - additional rows to display content (within the same "tr" tag)
    tr - additional rows ("tr" tag)
	
	source: https://code.tutsplus.com/articles/how-to-add-custom-fields-to-attachments--wp-31100

*/

$attchments_options = array(
	'image_video_url' => array(
        'label'       => __( 'External Video Url', 'xoxo-core' ),
        'input'       => 'text',
        'helps'       => __( 'Url from Youtube or Vimeo', 'xoxo-core' ),
        'application' => 'image',
        'exclusions'  => array( 'audio', 'video' ),
    ),
);

class Xoxo_Custom_Media_Fields {
 
    private $media_fields = array();
 
    function __construct( $fields ) {
 		$this->media_fields = $fields;
 
		add_filter( 'attachment_fields_to_edit', array( $this, 'applyFilter' ), 11, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'saveFields' ), 11, 2 );
    }
 
 
 
    public function applyFilter( $form_fields, $post = null ) {
 		// If our fields array is not empty
		if ( ! empty( $this->media_fields ) ) {
			// We browse our set of options
			foreach ( $this->media_fields as $field => $values ) {
				// If the field matches the current attachment mime type
				// and is not one of the exclusions
				if ( preg_match( "/" . $values['application'] . "/", $post->post_mime_type) && ! in_array( $post->post_mime_type, $values['exclusions'] ) ) {
					// We get the already saved field meta value
					$meta = get_post_meta( $post->ID, '_' . $field, true );
	 
					// Define the input type to 'text' by default
					switch ( $values['input'] ) {
						default:
						case 'text':
							$values['input'] = 'text';
							break;
					 
						case 'textarea':
							$values['input'] = 'textarea';
							break;
					 
						case 'select':
					 
							// Select type doesn't exist, so we will create the html manually
							// For this, we have to set the input type to 'html'
							$values['input'] = 'html';
					 
							// Create the select element with the right name (matches the one that wordpress creates for custom fields)
							$html = '<select name="attachments[' . $post->ID . '][' . $field . ']">';
					 
							// If options array is passed
							if ( isset( $values['options'] ) ) {
								// Browse and add the options
								foreach ( $values['options'] as $k => $v ) {
									// Set the option selected or not
									if ( $meta == $k )
										$selected = ' selected="selected"';
									else
										$selected = '';
					 
									$html .= '<option' . $selected . ' value="' . $k . '">' . $v . '</option>';
								}
							}
					 
							$html .= '</select>';
					 
							// Set the html content
							$values['html'] = $html;
					 
							break;
					 
						case 'checkbox':
					 
							// Checkbox type doesn't exist either
							$values['input'] = 'html';
					 
							// Set the checkbox checked or not
							if ( $meta == 'on' )
								$checked = ' checked="checked"';
							else
								$checked = '';
					 
							$html = '<input' . $checked . ' type="checkbox" name="attachments[' . $post->ID . '][' . $field . ']" id="attachments-' . $post->ID . '-' . $field . '" />';
					 
							$values['html'] = $html;
					 
							break;
					 
						case 'radio':
					 
							// radio type doesn't exist either
							$values['input'] = 'html';
					 
							$html = '';
					 
							if ( ! empty( $values['options'] ) ) {
								$i = 0;
					 
								foreach ( $values['options'] as $k => $v ) {
									if ( $meta == $k )
										$checked = ' checked="checked"';
									else
										$checked = '';
					 
									$html .= '<input' . $checked . ' value="' . $k . '" type="radio" name="attachments[' . $post->ID . '][' . $field . ']" id="' . sanitize_key( $field . '_' . $post->ID . '_' . $i ) . '" /> <label for="' . sanitize_key( $field . '_' . $post->ID . '_' . $i ) . '">' . $v . '</label><br />';
									$i++;
								}
							}
					 
							$values['html'] = $html;
					 
							break;
					}
	 
					// And set it to the field before building it
					$values['value'] = $meta;
	 
					// We add our field into the $form_fields array
					$form_fields[$field] = $values;
				}
			}
		}
	 
		// We return the completed $form_fields array
		return $form_fields;
		}
 
 
 
    function saveFields( $post, $attachment ) {
 		// If our fields array is not empty
		if ( ! empty( $this->media_fields ) ) {
			// Browser those fields
			foreach ( $this->media_fields as $field => $values ) {
				// If this field has been submitted (is present in the $attachment variable)
				if ( isset( $attachment[$field] ) ) {
					// If submitted field is empty
					// We add errors to the post object with the "error_text" parameter we set in the options
					if ( strlen( trim( $attachment[$field] ) ) == 0 )
						$post['errors'][$field]['errors'][] = __( $values['error_text'] );
					// Otherwise we update the custom field
					else
						update_post_meta( $post['ID'], '_' . $field, $attachment[$field] );
				}
				// Otherwise, we delete it if it already existed
				else {
					delete_post_meta( $post['ID'], $field );
				}
			}
		}
	 
		return $post;
    }
 
}
 
$cmf = new Xoxo_Custom_Media_Fields( $attchments_options );
