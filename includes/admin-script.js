jQuery(document).ready(function($) {
    // Only run if wp.media is available
    if (typeof wp === 'undefined' || !wp.media) {
        return;
    }

    // Extend the AttachmentsBrowser to add our custom filter
    var originalAttachmentsBrowser = wp.media.view.AttachmentsBrowser;
    
    wp.media.view.AttachmentsBrowser = originalAttachmentsBrowser.extend({
        initialize: function() {
            // Call original initialize
            originalAttachmentsBrowser.prototype.initialize.apply(this, arguments);
            
            // Listen for the toolbar to be ready
            this.on('ready', this.createCategoryFilter, this);
        },
        
        createCategoryFilter: function() {
            // Check if filter already exists to avoid duplicates
            if (this.$el.find('.iml-media-category-filter').length) {
                return;
            }
            
            var self = this;
            
            // Create the select element
            // We need to fetch categories first. Since this is JS, we might need to rely on localized script data
            // or fetch them via AJAX. For simplicity, let's try to fetch via standard WP AJAX or assume localized data.
            // A better way in backbone is to extend the filters view, but injecting it into the toolbar is often easier for simple needs.
            
            // Let's assume we can use the standard wp.media.view.AttachmentFilters.Taxonomy if available,
            // but often it requires 'manage' permissions or specific configuration.
            
            // Simpler approach: Create a standard select and trigger change on the collection props.
            
            // Fetch categories using WP API or admin-ajax would be robust, 
            // but let's see if we can trigger the native one first.
            
            // Force the 'uploadedTo' filter (standard in WP) to be joined by a Category filter?
            // Actually, WP 5.8+ supports this natively if we filter 'ajax_query_attachments_args'.
            
            // Let's try to inject a simple dropdown if it's not present.
            // We need the categories. We'll fetch them once.
            
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'iml_get_media_categories' // We need to register this ajax action
                },
                success: function(response) {
                    if (!response.success) return;
                    
                    var categories = response.data;
                    var $select = $('<select class="attachment-filters iml-media-category-filter"><option value="all">All Categories</option></select>');
                    
                    $.each(categories, function(index, cat) {
                        $select.append('<option value="' + cat.slug + '">' + cat.name + '</option>');
                    });
                    
                    // Insert into the toolbar
                    // The toolbar usually has .media-toolbar-secondary or .media-toolbar-primary
                    var $toolbar = self.$el.find('.media-toolbar-secondary');
                    if ($toolbar.length) {
                        $toolbar.append($select);
                    } else {
                        // Fallback
                        self.$el.find('.media-toolbar').append($select);
                    }
                    
                    // Handle change
                    $select.on('change', function() {
                        var val = $(this).val();
                        // Update the collection query
                        if (val === 'all') {
                            // Remove the category query
                            delete self.model.props.attributes.category_name; // 'category_name' is for slugs
                        } else {
                            // Set the category query
                            self.model.props.set('category_name', val);
                        }
                        
                        // Refresh the query
                        self.model.props.trigger('change'); // Trigger change to refresh
                        // Alternatively: self.collection.props.set({ category_name: val });
                    });
                }
            });
        }
    });
});
