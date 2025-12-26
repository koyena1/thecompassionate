# File Upload Fix: Instruction Title-Based Naming

## Problem Fixed
Previously, when patients uploaded files, they were saved with their original filenames (e.g., "hero1.jpg", "mydoc.pdf"). This made it difficult to identify which instruction the file was responding to.

## Solution Implemented
Files are now automatically renamed based on the **instruction title** that the patient is responding to.

### Example:
- **Instruction Title**: "Sugar test"
- **Original Filename**: "hero1.jpg"
- **New Filename**: "Sugar_test_123_1735059420.jpg"
  - Format: `{InstructionTitle}_{PatientID}_{Timestamp}.{extension}`

## Changes Made

### 1. Database Schema Update
- Added `instruction_title` column to `patient_uploads` table
- This stores the instruction title associated with each upload

### 2. Patient Documentation Page (`patient/documentation.php`)
- Modified upload logic to:
  - Capture the current instruction title
  - Sanitize the title for use in filenames (remove special characters)
  - Rename uploaded files using the instruction title
  - Store the instruction title in the database

### 3. Admin View (`admin/clinical_updates_documentation.php`)
- Updated the "Received Patient Documents" table to display:
  - **Primary**: Instruction title (bold)
  - **Secondary**: Actual filename (small text below)

## How to Apply

### Step 1: Run Database Migration
1. Open your browser
2. Navigate to: `http://localhost/psychiatrist/migrate_instruction_title.php`
3. The script will automatically add the required column
4. **Delete the migration file after running** for security

### Step 2: Test the Feature
1. Login as a patient
2. Go to Documentation page
3. You'll see the current instruction (e.g., "Sugar test")
4. Upload a file
5. The file will be saved with the instruction title in its name

### Step 3: Verify in Admin Panel
1. Login as admin
2. Go to Clinical Updates & Documentation
3. Check "Received Patient Documents" section
4. You should see the instruction title displayed for newly uploaded files

## Benefits
✅ **Better Organization**: Files are named meaningfully  
✅ **Easy Identification**: Know which instruction each file relates to  
✅ **Professional**: No more random filenames like "hero1.jpg"  
✅ **Backward Compatible**: Old uploads still work (shown with "General Document")

## File Naming Rules
- Spaces → Underscores: "Sugar test" → "Sugar_test"
- Special characters removed: "Test@123!" → "Test_123"
- Multiple underscores reduced: "Test___file" → "Test_file"
- Patient ID and timestamp added for uniqueness

## Example Scenarios

| Instruction Title | Original File | New Filename |
|------------------|---------------|--------------|
| Sugar test | report.pdf | Sugar_test_5_1735059420.pdf |
| Blood Pressure Report | hero1.jpg | Blood_Pressure_Report_5_1735059421.jpg |
| General Guidance | scan.png | General_Guidance_5_1735059422.png |

## Notes
- Old uploads (before this fix) will show "General Document" as their instruction title
- The actual file remains accessible at its stored path
- Both the instruction title and filename are shown in admin view for reference
