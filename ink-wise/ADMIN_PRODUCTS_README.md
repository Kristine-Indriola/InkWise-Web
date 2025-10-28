# Admin Product System Documentation

## Overview
The InkWise admin product system provides comprehensive management of invitation, giveaway, and envelope products with full template integration and CRUD operations.

## Architecture

### Models
- **Product**: Main product model with relationships to templates and various product components
- **Template**: Template definitions with design specifications (paper stocks, addons, colors, bulk orders)
- **ProductImage**: Product image storage (front/back/preview)
- **ProductPaperStock**: Available paper stock options for products
- **ProductAddon**: Additional product features (foil, embossing, etc.)
- **ProductColor**: Available color options
- **ProductBulkOrder**: Bulk pricing tiers
- **ProductEnvelope**: Envelope-specific details

### Controllers
- **ProductController**: Handles all admin product operations
  - `index()`: Display product listing with summary cards
  - `createInvitation()`: Show invitation creation form
  - `createGiveaway()`: Show giveaway creation form
  - `createEnvelope()`: Show envelope creation form
  - `store()`: Process product creation from templates
  - `edit()`: Show product editing form
  - `view()`: Display product details
  - `destroy()`: Delete products
  - `getTemplateData()`: AJAX endpoint for template data

### Views
- `resources/views/admin/products/index.blade.php`: Main dashboard with product grid
- `resources/views/admin/products/create-invitation.blade.php`: Multi-page invitation creation
- `resources/views/admin/products/create-giveaway.blade.php`: Giveaway creation form
- `resources/views/admin/products/create-envelope.blade.php`: Envelope creation form
- `resources/views/admin/products/edit.blade.php`: Product editing interface
- `resources/views/admin/products/view.blade.php`: Product detail view
- `resources/views/admin/products/templates.blade.php`: Template selection interface

### Routes
All routes are prefixed with `admin.products.*`:
- `index`: GET /admin/products
- `create.invitation`: GET /admin/products/create/invitation
- `create.giveaway`: GET /admin/products/create/giveaway
- `create.envelope`: GET /admin/products/create/envelope
- `store`: POST /admin/products/store
- `edit`: GET /admin/products/{id}/edit
- `view`: GET /admin/products/{id}/view
- `destroy`: DELETE /admin/products/{id}
- `template.data`: GET /admin/products/template/{id}/data

## Key Features

### Template Integration
- Products are created from templates with pre-defined specifications
- Template data includes paper stocks, addons, colors, and bulk pricing
- Dynamic form population based on selected templates

### Multi-Product Type Support
- **Invitations**: Wedding, birthday, corporate events
- **Giveaways**: Promotional items and party favors
- **Envelopes**: Matching envelopes for invitations

### Dynamic Form Fields
- Paper stock selection with pricing
- Addon options (foil, embossing, etc.)
- Color customization
- Bulk order pricing tiers
- Lead time management

### Image Management
- Front/back/preview image handling
- Template image integration
- ImageResolver utility for consistent URL generation

## Testing

### Automated Test Script
Run `php test_admin_products.php` to verify:
- Template availability and distribution
- Product CRUD operations
- Template relationship integrity
- Route resolution
- Controller method existence
- Summary count calculations

### Manual Testing Checklist
1. **Template Selection**: Verify templates load and filter correctly by product type
2. **Product Creation**: Test creating products from each template type
3. **Form Validation**: Ensure all required fields are validated
4. **Image Upload**: Test front/back/preview image uploads
5. **Dynamic Fields**: Verify paper stocks, addons, colors populate from templates
6. **Product Editing**: Test updating all product fields
7. **Product Viewing**: Check detail view displays all information correctly
8. **Bulk Operations**: Test bulk delete functionality

## Database Schema

### Products Table
```sql
- id (primary key)
- template_id (foreign key to templates)
- name
- event_type
- product_type (Invitation/Giveaway/Envelope)
- theme_style
- description
- base_price
- lead_time
- lead_time_days
- date_available
- timestamps
```

### Templates Table
```sql
- id (primary key)
- name
- product_type
- event_type
- theme_style
- description
- front_image
- back_image
- design (JSON: paper_stocks, addons, colors, bulk_orders)
- timestamps
```

## Maintenance

### Regular Tasks
1. Run automated tests weekly: `php test_admin_products.php`
2. Monitor template count and distribution
3. Verify image paths are accessible
4. Check for broken relationships in database

### Troubleshooting
- **Template relationship issues**: Check Product model `template()` method points to correct model
- **Missing routes**: Verify routes are registered in `routes/web.php`
- **Form not populating**: Check AJAX calls to `getTemplateData()` method
- **Images not displaying**: Verify ImageResolver paths and file permissions

## Security Considerations
- All admin routes protected by authentication middleware
- Mass assignment protection via `$fillable` arrays
- Input validation on all forms
- File upload restrictions for images

## Performance Notes
- Eager loading used for relationships in views
- Template data cached in session during creation process
- Image optimization recommended for large files
- Database indexes on frequently queried columns (product_type, template_id)