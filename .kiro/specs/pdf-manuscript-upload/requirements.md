# Requirements Document

## Introduction

### Purpose
This document specifies the requirements for adding PDF manuscript upload functionality to the ICSS ESHET Tabulation System. This feature will allow administrators to upload research paper manuscripts (PDF files) when managing papers, and enable evaluators to view these manuscripts during the evaluation process.

### Scope
The feature covers:
- PDF file upload interface in admin paper management
- Secure file storage and access control
- PDF viewing capability for evaluators in their assigned tracks
- File management (upload, replace, delete)

Out of scope:
- PDF editing, annotation, or markup features
- Multiple versions/revisions of manuscripts
- Automated PDF processing or analysis
- Bulk manuscript uploads

### Target Users
- **Administrators**: Conference organizers who manage papers and need to upload manuscripts
- **Evaluators**: Panel members who evaluate papers and need to view full manuscripts

### Background
Currently, the system manages paper metadata (title, researcher, affiliation, track) but does not store or provide access to the actual manuscript files. Evaluators have requested access to full papers to improve evaluation quality. This feature addresses that need while maintaining proper access control based on track assignments.

## Requirements

### Functional Requirements

#### FR-1: Manuscript Upload (Admin)
**Priority**: Must Have  
**Description**: Administrators shall be able to upload PDF manuscript files when creating or editing papers.

**User Story**: As an administrator, I want to upload a PDF manuscript file when adding a new paper, so that evaluators have access to the full research paper during evaluation.

**Acceptance Criteria**:
- PDF file input field appears in "Add Paper" dialog
- PDF file input field appears in "Edit Paper" dialog
- File type restricted to PDF only (validation on client and server)
- Maximum file size limited to 10MB
- Upload progress indicator displays during file upload
- Success message shown on successful upload
- Error message shown with specific reason on upload failure
- Field is optional - papers can exist without manuscripts

**Dependencies**: Laravel file storage, file validation

---

#### FR-2: Manuscript Storage
**Priority**: Must Have  
**Description**: System shall store uploaded manuscripts securely with proper organization and cleanup.

**Acceptance Criteria**:
- PDF files stored in Laravel storage (not web-accessible public directory)
- Filenames sanitized and made unique to prevent collisions
- Original filename preserved in database for display purposes
- Files organized in subdirectories by track or paper ID
- Database column added to papers table for manuscript path
- Proper MIME type and file extension validation
- File integrity verified after upload

**Dependencies**: Laravel storage configuration, database migration

---

#### FR-3: Manuscript Status Indicator (Admin)
**Priority**: Should Have  
**Description**: Administrators shall be able to see which papers have manuscripts uploaded.

**User Story**: As an administrator, I want to see which papers have manuscripts uploaded in the papers list, so that I can quickly identify incomplete paper records.

**Acceptance Criteria**:
- Papers list displays icon/badge for papers with manuscripts
- Icon is clearly distinguishable (color, shape, or both)
- No indicator shown for papers without manuscripts
- Hovering icon shows tooltip (e.g., "Manuscript uploaded")
- Clicking icon opens PDF in new tab for admin preview

**Dependencies**: FR-1, FR-2

---

#### FR-4: Manuscript Replacement (Admin)
**Priority**: Must Have  
**Description**: Administrators shall be able to replace existing manuscript files.

**User Story**: As an administrator, I want to add or replace a PDF manuscript for an existing paper, so that I can update manuscripts after initial paper creation.

**Acceptance Criteria**:
- When editing paper with existing manuscript, current filename is displayed
- "Replace manuscript" option allows uploading new file
- Old file deleted from storage when replaced
- Confirmation shown before replacement
- New manuscript validated same as initial upload
- Transaction ensures database and filesystem stay synchronized

**Dependencies**: FR-1, FR-2

---

#### FR-5: Manuscript Deletion (Admin)
**Priority**: Must Have  
**Description**: Administrators shall be able to remove manuscript files without deleting the paper.

**User Story**: As an administrator, I want to remove an uploaded manuscript from a paper, so that I can manage incorrect uploads or withdraw papers.

**Acceptance Criteria**:
- "Delete manuscript" button available when editing paper with manuscript
- Confirmation dialog shown before deletion
- File removed from storage
- Database field set to NULL
- Paper record remains intact
- Success/error message shown

**Dependencies**: FR-2

---

#### FR-6: Manuscript Viewing (Evaluator)
**Priority**: Must Have  
**Description**: Evaluators shall be able to view manuscripts for papers in their assigned tracks.

**User Story**: As an evaluator, I want to click a button to view a paper's manuscript, so that I can read the full research paper while evaluating.

**Acceptance Criteria**:
- "View Manuscript" button appears next to papers with manuscripts in workspace
- Button uses PDF icon for clear indication
- Button disabled or hidden for papers without manuscripts
- Clicking button opens PDF in new browser tab
- PDF served with correct content-type header (application/pdf)
- Loading indicator shows while PDF loads
- Works in Chrome, Firefox, Edge, Safari

**Dependencies**: FR-2, FR-7

---

#### FR-7: Access Control
**Priority**: Must Have  
**Description**: System shall enforce proper access control for manuscript viewing.

**User Story**: As an evaluator, I want to only access manuscripts for papers in my assigned tracks, so that evaluation integrity is maintained.

**Acceptance Criteria**:
- Only authenticated users can access manuscripts
- Administrators can view all manuscripts
- Evaluators can only view manuscripts for papers in their assigned tracks
- Direct URL access to manuscripts is protected by middleware
- Unauthorized access returns 403 Forbidden error
- Track assignment validation performed on every manuscript request
- Authorization checks logged for audit trail

**Dependencies**: Existing authentication and track assignment system

---

#### FR-8: File Cleanup on Paper Deletion
**Priority**: Must Have  
**Description**: System shall automatically clean up manuscript files when papers are deleted.

**Acceptance Criteria**:
- When paper is deleted, associated manuscript file is deleted from storage
- Deletion handled in Paper model's delete event or cascade
- Failed file deletion logged but doesn't block paper deletion
- Orphaned files (files without database record) can be identified
- Command or admin tool available to clean up orphaned files

**Dependencies**: FR-2

---

### Non-Functional Requirements

#### NFR-1: Performance
**Priority**: Must Have  
- File upload completes within 30 seconds for 10MB file
- PDF viewing/download starts within 3 seconds
- Multiple simultaneous uploads supported without performance degradation
- File storage operations don't block other system functions

---

#### NFR-2: Security
**Priority**: Must Have  
- Manuscripts stored outside web root to prevent direct access
- Access control enforced at application layer
- File uploads validated for type and size
- Secure filenames prevent directory traversal attacks
- HTTPS enforced for all manuscript operations

---

#### NFR-3: Usability
**Priority**: Should Have  
- Upload interface follows existing UI design patterns
- File size and type restrictions communicated clearly
- Progress feedback during upload
- Error messages are specific and actionable
- Interface works on desktop and tablet devices

---

#### NFR-4: Reliability
**Priority**: Must Have  
- Failed uploads don't corrupt database
- Transaction integrity between database and filesystem
- Graceful degradation if file storage temporarily unavailable
- File operations are atomic where possible

---

#### NFR-5: Maintainability
**Priority**: Should Have  
- Storage path configurable via Laravel config
- Support for local filesystem (current deployment)
- Code structured to allow future migration to S3/cloud storage
- File operations abstracted for testability

---

### Business Rules

#### BR-1: File Requirements
- Only PDF format accepted
- Maximum file size: 10MB per manuscript
- One manuscript per paper (no versioning)
- Minimum file size: 1KB (to prevent empty uploads)

#### BR-2: Access Permissions
- Admin users: Full CRUD operations on all manuscripts
- Evaluators: Read-only access to manuscripts in assigned tracks only
- Guests/unauthenticated: No access

#### BR-3: Optional Feature
- Manuscripts are optional for all papers
- Evaluation can proceed without manuscripts
- No retroactive requirements for existing papers

#### BR-4: Track Assignment Dependency
- Manuscript access follows existing track assignment logic
- Losing track assignment immediately revokes manuscript access
- Admin access unaffected by track assignments

---

### Data Requirements

#### DR-1: Database Schema Changes
- Add `manuscript_path` column to `papers` table
  - Type: `string` (nullable)
  - Stores relative path from storage root
- Add `manuscript_original_name` column to `papers` table
  - Type: `string` (nullable)
  - Stores original uploaded filename for display
- Add index on `manuscript_path` for faster lookups

#### DR-2: File Storage Structure
```
storage/
  app/
    manuscripts/
      track-{id}/
        paper-{id}-{hash}.pdf
```

---

### External Interface Requirements

#### EIR-1: File Upload API
- **Endpoint**: POST `/admin/papers/{id}/manuscript`
- **Content-Type**: `multipart/form-data`
- **Request**: File in `manuscript` field
- **Response**: JSON with success/error message
- **Authentication**: Required (admin only)

#### EIR-2: File Download/View API
- **Endpoint**: GET `/manuscripts/{id}`
- **Response**: PDF file with `application/pdf` content-type
- **Authentication**: Required (admin or assigned evaluator)
- **Authorization**: Track assignment validation

---

## Constraints

### Technical Constraints
1. **Storage Capacity**: Hostinger hosting plan has limited storage - must monitor usage
2. **PHP Upload Limits**: Server `upload_max_filesize` and `post_max_size` must be ≥ 10MB
3. **Browser Support**: PDF viewing relies on browser native capabilities
4. **No Server-Side Rendering**: PDFs served as-is, no generation or modification

### Operational Constraints
1. **Backward Compatibility**: Existing papers without manuscripts must continue working
2. **No Bulk Upload**: Initial version handles one file at a time
3. **Manual Process**: Administrators must upload manuscripts individually per paper

---

## Assumptions

1. Users have modern browsers capable of displaying PDFs (Chrome 90+, Firefox 88+, Edge 90+, Safari 14+)
2. Server has sufficient storage (estimate: 1GB for 100 papers at 10MB each)
3. PDF files are provided by administrators, not generated by system
4. Network bandwidth supports 10MB file transfers
5. All manuscripts are legitimate academic papers in PDF format
6. Administrators have source PDF files ready for upload

---

## Dependencies

### Internal Dependencies
- Existing Paper model and database table
- Existing track assignment system
- Existing authentication and authorization middleware
- Laravel storage filesystem
- Admin papers management interface
- Evaluator workspace interface

### External Dependencies
- Browser PDF viewing capability
- Server filesystem with write permissions
- PHP fileinfo extension for MIME type detection

---

## Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Storage exhaustion | High | Medium | Monitor storage usage; implement file size limits; add storage alerts |
| Browser incompatibility | Medium | Low | Test on target browsers; provide download fallback; document supported browsers |
| Upload failures due to network | Medium | Medium | Implement retry mechanism; clear error messages; validate before full upload |
| Unauthorized access | High | Low | Comprehensive authorization testing; security audit; access logging |
| File corruption | Medium | Low | Validate PDFs after upload; checksum verification; backup strategy |

---

## Success Criteria

1. ✅ Admin can upload manuscript in < 2 minutes per paper
2. ✅ 100% of valid PDFs are viewable by authorized evaluators
3. ✅ Zero security incidents (unauthorized manuscript access)
4. ✅ < 5% upload failure rate (excluding user errors)
5. ✅ Zero database corruption from file operations
6. ✅ Feature adopted for >80% of papers within first conference

---

## Open Questions

1. **File Size Limit**: Is 10MB sufficient, or should it be configurable? Some research papers with many high-resolution images may exceed this.

2. **Storage Location**: Hostinger deployment - what is the total available storage? Should we implement storage quotas?

3. **Download vs View Only**: Should evaluators be able to download PDFs for offline viewing, or browser-only viewing?

4. **Required vs Optional**: Should manuscript upload become required for future papers, or remain optional indefinitely?

5. **Bulk Operations**: Priority for bulk manuscript upload feature? Would significantly increase development time.

6. **File Naming Convention**: Should stored files include paper number in filename for easier manual server management?

7. **Backup Strategy**: Are manuscript files included in existing backup procedures, or need separate backup plan?

8. **Retention Policy**: Should manuscripts be retained after conference ends? For how long?

## Glossary

- **Manuscript**: The full research paper document in PDF format submitted for evaluation
- **Paper**: A research submission record in the system (metadata like title, author, track)
- **Track**: A thematic category grouping related research papers
- **Evaluator**: A user assigned to review and score papers in specific tracks
- **Administrator**: A user with full system access who manages papers, tracks, and evaluators
- **Upload**: The process of transferring a PDF file from admin's computer to the server
- **View/Download**: The process of evaluators accessing and displaying manuscript PDFs
- **Storage**: Server filesystem location where uploaded manuscripts are kept
- **Access Control**: Security mechanism ensuring only authorized users can view specific manuscripts
- **Track Assignment**: The relationship between evaluators and tracks they are permitted to evaluate