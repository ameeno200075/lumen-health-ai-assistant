# Screenshot checklist

Capture these screens from the running application after signing in with suitable demo accounts. Use realistic but fictional patient data; never publish real patient names, email addresses, appointment details, API keys, or other personal information.

## Recommended captures

| File | What to capture | Notes |
| --- | --- | --- |
| `patient-dashboard.png` | Authenticated patient dashboard | Show the main navigation and summary state. |
| `assistant-chat.png` | AI receptionist conversation | Use fictional data and avoid displaying secrets. |
| `doctor-search.png` | Doctor discovery results | Show real seeded clinic records. |
| `availability.png` | Availability for a selected doctor and date | Ensure the date and slots are demo data. |
| `booking-confirmation.png` | Successful appointment confirmation | Use a fictional patient identity. |
| `admin-dashboard.png` | Administrator dashboard | Capture with an admin demo account. |
| `doctor-management.png` | Doctor and weekly availability management | Hide any private operational data. |

## Capture guidelines

- Save final images in this directory using the exact filenames above.
- Prefer PNG captures at a consistent viewport size.
- Crop browser chrome where it does not add useful context.
- Keep the interface readable; avoid tiny text and excessive empty space.
- Use only synthetic/demo records. Do not expose real patient information.
- Do not add placeholder images that imply a feature is implemented when it is not.

When images have been added, reference them from the root README with relative Markdown paths, for example:

```markdown
![AI assistant conversation](docs/screenshots/assistant-chat.png)
```
