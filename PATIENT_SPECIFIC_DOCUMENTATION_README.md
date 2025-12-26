# Patient-Specific Documentation Feature

## Overview
This feature allows the admin to create individual instructions for specific patients in addition to global instructions that all patients can see.

## Database Setup

### Step 1: Run the SQL Migration
Execute the SQL file to update your database structure:
```sql
-- Run this in phpMyAdmin or MySQL console
SOURCE documentation_patient_specific.sql;
```

Or manually run the queries from `documentation_patient_specific.sql` in phpMyAdmin.

## How It Works

### Admin Side (clinical_updates_documentation.php)

#### Creating Instructions

1. **Global Instructions** (All Patients)
   - Leave the patient dropdown at "🌍 Global Instruction (All Patients)"
   - Enter the title and content
   - Click "Save Instruction"
   - This instruction will be visible to ALL patients who don't have a specific instruction

2. **Patient-Specific Instructions**
   - Select a patient from the dropdown (e.g., "👤 John Doe")
   - Enter a custom title (e.g., "Blood Test Results", "Medication Update")
   - Enter the instruction content
   - Click "Save Instruction"
   - This instruction will ONLY be visible to that specific patient

#### Managing Instructions

- **View All Patient-Specific Instructions**: A new table shows all patient-specific instructions with:
  - Patient Name
  - Instruction Title (instead of generic "Documentation")
  - Last Updated timestamp
  - Edit button to modify

- **Edit Existing Instructions**: Click the "Edit" button next to any patient-specific instruction to modify it

### Patient Side (documentation.php)

#### What Patients See

1. **If they have a specific instruction**: They will see their personalized instruction with the custom title
2. **If they don't have a specific instruction**: They will see the global instruction

The system automatically prioritizes patient-specific instructions over global ones.

## Features Implemented

✅ **Patient Dropdown**: Admin can select any patient to create specific instructions
✅ **Instruction Title Display**: Shows meaningful titles instead of filenames
✅ **Patient-Specific Table**: New table showing all patient-specific instructions
✅ **Edit Functionality**: Easy editing of existing instructions
✅ **Fallback System**: Patients without specific instructions see global ones
✅ **Database Schema**: Properly structured with foreign keys and indexes

## Example Use Cases

1. **Blood Test Results**: Admin creates instruction titled "Your Blood Test Results" for Patient A with specific values and recommendations

2. **Medication Changes**: Admin creates instruction titled "Medication Update - December 2025" for Patient B with new prescription details

3. **Follow-up Instructions**: Admin creates instruction titled "Post-Therapy Follow-up" for Patient C with specific exercises

4. **General Guidance**: Global instruction titled "Clinical Guidelines" visible to all patients without specific instructions

## Technical Details

### Database Structure
```
documentation table:
- id (Primary Key)
- patient_id (Foreign Key to patients table, NULL for global)
- title (Instruction title)
- content (Instruction content)
- created_at (Timestamp)
- updated_at (Timestamp)
```

### Query Logic
- Admin can create/update instructions for any patient or globally
- Patient page automatically fetches patient-specific instruction if exists
- Falls back to global instruction (patient_id = NULL) if no specific one exists

## Files Modified

1. **documentation_patient_specific.sql** - Database migration script
2. **admin/clinical_updates_documentation.php** - Admin interface with patient dropdown
3. **patient/documentation.php** - Patient view with personalized instructions

## Security Notes

- All inputs are sanitized using `mysqli_real_escape_string()`
- Patient authentication is maintained
- Foreign key constraints ensure data integrity
- Only admins can create/edit instructions (auth_check.php)

## Future Enhancements (Optional)

- Delete instruction functionality
- Instruction history/versioning
- Rich text editor for content
- Email notification when new instruction is created
- Bulk instruction creation for multiple patients
