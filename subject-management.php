<?php
/*
Plugin Name: Subject Management
Description: Plugin to manage subjects.
Version: 1.0
Author: Shlok Jani
*/

// Create table on plugin activation
function create_subjects_table() {
    global $wpdb;
    $wp_subjects = $wpdb->prefix . 'subjects';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $wp_subjects (
        id INT NOT NULL AUTO_INCREMENT,
        menu_name VARCHAR(255),
        position INT(3),
        visible TINYINT(1),
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_subjects_table');

// Add admin menu item for subjects management
function subject_management_menu() {
    add_menu_page(
        'Subjects Management',
        'Subjects',
        'manage_options',
        'subjects-management',
        'subject_management_page'
    );
}
add_action('admin_menu', 'subject_management_menu');


// Subjects management page callback function
function subject_management_page() {
    global $wpdb;
    $wp_subjects = $wpdb->prefix . 'subjects';

    // Handle form submissions
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$menu_name = isset($_POST['menu_name']) ? sanitize_text_field($_POST['menu_name']) : '';
		$position = isset($_POST['position']) ? intval($_POST['position']) : 0;
		$visible = isset($_POST['visible']) ? 1 : 0;
		$id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
	
		// Check for duplicates
		$duplicate_name = $wpdb->get_row("SELECT * FROM $wp_subjects WHERE menu_name = '$menu_name' AND id != $id");
		$duplicate_position = $wpdb->get_row("SELECT * FROM $wp_subjects WHERE position = '$position' AND id != $id");
	
	
		if ($duplicate_name) {
			echo '<div class="error"><p>Error: Duplicate menu name.</p></div>';
		} elseif ($duplicate_position) {
			echo '<div class="error"><p>Error: Duplicate position.</p></div>';
		} elseif ($position < 0) {
            echo '<div class="error"><p>Error: Position cannot be a negative number.</p></div>';
		}
		else {
			if (isset($_POST['add'])) {
				// Add new subject
				$wpdb->insert(
					$wp_subjects,
					array(
						'menu_name' => $menu_name,
						'position' => $position,
						'visible' => $visible
					)
				);
				echo '<div class="updated"><p>Success: Subject added.</p></div>';
			} elseif (isset($_POST['edit'])) {
				// Edit existing subject
				$id = intval($_POST['subject_id']);
				$wpdb->update(
					$wp_subjects,
					array(
						'menu_name' => $menu_name,
						'position' => $position,
						'visible' => $visible
					),
					array('id' => $id)
				);
				echo '<div class="updated"><p>Success: Subject edited.</p></div>';
			}
		}
		
	
		if (isset($_POST['delete'])) {
			// Delete subject
			$id = intval($_POST['subject_id']);
			$wpdb->delete(
				$wp_subjects,
				array('id' => $id)
			);
			echo '<div class="updated"><p>Success: Subject deleted.</p></div>';
		}
	}

    // Display subjects management form
    ?>
    <div class="wrap">
        <h2>Subjects Management</h2>
        <form method="post" action="">
        <label for="menu_name">Menu Name:</label>
        <?php
        $menu_name_value = '';
        $position_value = '';
        if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['add']) || isset($_POST['edit']) || isset($_POST['delete']))) {
            // Form was submitted, clear the input fields
            $menu_name_value = '';
            $position_value = '';
        } elseif (isset($_POST['menu_name']) && isset($_POST['position'])) {
            // Form was not submitted, keep the existing values
            $menu_name_value = esc_attr($_POST['menu_name']);
            $position_value = esc_attr($_POST['position']);
        }
        ?>
        <input type="text" id="menu_name" name="menu_name" value="<?php echo $menu_name_value; ?>" required>
        <label for="position">Position:</label>
        <input type="number" id="position" name="position" value="<?php echo $position_value; ?>" required>
        <label for="visible">Visible:</label>
        <input type="checkbox" id="visible" name="visible" value="1" <?php echo isset($_POST['visible']) && $_POST['visible'] == 1 ? 'checked' : ''; ?>>
        <input type="submit" name="add" value="Add Subject" class="button-primary">
        <input type="hidden" name="subject_id" value="">
    </form>

		<!-- Display existing subjects -->
		<h2>Existing Subjects</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Menu Name</th>
					<th>Position</th>
					<th>Visible</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php
    $subjects = $wpdb->get_results("SELECT * FROM $wp_subjects ORDER BY position ASC", ARRAY_A);
    foreach ($subjects as $subject) {
        ?>
			<tr>
				<form method="post" action="">
					<td><?php echo $subject['id']; ?></td>
					<td><input type="text" name="menu_name" value="<?php echo esc_attr($subject['menu_name']); ?>" required></td>
					<td><input type="number" name="position" value="<?php echo esc_attr($subject['position']); ?>" required></td>
					<td><input type="checkbox" name="visible" value="1" <?php echo $subject['visible'] ? 'checked' : ''; ?>></td>
					<td>
						<input type="hidden" name="subject_id" value="<?php echo esc_attr($subject['id']); ?>">
						<input type="submit" name="edit" value="Edit" class="button-secondary">
						<input type="submit" name="delete" value="Delete" class="button-secondary">
					</td>
				</form>
			</tr>
			<?php
			}
			?>
			</tbody>
		</table>
        
    </div>
    <?php
}

// Shortcode to display subjects on the front end
function display_subjects_shortcode() {
    global $wpdb;
    $wp_subjects = $wpdb->prefix . 'subjects';
    $results = $wpdb->get_results("SELECT * FROM $wp_subjects WHERE visible = 1 ORDER BY position ASC LIMIT 50", ARRAY_A);

    $output = '<ul>';
    foreach ($results as $subject) {
        $output .= '<li>' . $subject['menu_name'] . '</li>';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('SUBJECTS', 'display_subjects_shortcode');
