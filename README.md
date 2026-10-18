# Pokok ID

**Every tree gets an identity. Every resident gets a reason to care.**

Entry for the **Sime Darby Property University Hackathon 2026**
**Track 1:** Township of Tomorrow | **Focus area:** Planet (with People benefits)

*Alternative titles: TeduhMap, Canopy ID*

---

## Team

**Team name:** [Your Team Name]
**University:** [Your University]

| No. | Name | Role |
|-----|------|------|
| 1 | [Member 1 Name] | [e.g. Team Leader / Backend] |
| 2 | [Member 2 Name] | [e.g. Frontend / UI Design] |
| 3 | [Member 3 Name] | [e.g. Database / Data Collection] |
| 4 | [Member 4 Name] | [e.g. Pitch / Documentation] |

---

## Problem

- Township managers and developers have no street-level record of their trees. Existing tree data is manual, scattered and outdated.
- Nobody can say which streets have too few trees or where to plant first.
- Sick or damaged trees go unreported because residents have no connection to the trees around them.

## Solution

Pokok ID gives every tree in a township a digital identity, keeps that record alive through residents, and gives planners the data to decide where to plant next.

### Key features

1. **Tree map:** every tree stored with GPS coordinates, species, size and health status.
2. **Tree Passport:** a page per tree showing location, photo, size, health and report history.
3. **Resident participation:** add missed trees, correct marker positions, report problems and adopt a tree. Tagging takes under 30 seconds.
4. **Trees near me:** the phone's GPS lists the closest trees, with no tags or scanning needed.
5. **Duplicate check:** a new tree within 3 m of an existing one prompts "Is this the same tree?".
6. **Planner dashboard:** trees per 100 m by street (lowest first = plant here first), open reports and a health breakdown.
7. **Monitoring over time:** resident updates and yearly rechecks show growth or loss.

## Data sources (no drone required)

The prototype is data-source agnostic. Every tree records its `source` so the data mix stays transparent.

- Real trees tagged on foot in a pilot area using a phone
- Open data from OpenStreetMap as a starting inventory
- Clearly labelled demo trees for scale
- Optional AI crown detection on openly licensed aerial imagery, as a proof of concept

Drone scanning is planned for Phase 2, not a dependency of the prototype.

## Benefits

| Who | Benefit |
|-----|---------|
| Residents | A way to care for local trees (adopt, report, follow) and faster action on problems |
| Township and developer | A live tree inventory, data-driven planting priorities, early maintenance alerts, measurable tree density per street |
| Environment | Better-maintained trees and more canopy where it is needed most |

## Tech stack

- **Backend:** PHP (PDO), MySQL
- **Frontend:** HTML, CSS, JavaScript, Leaflet (map)
- **Distance calculations:** Haversine formula in SQL
- **Design:** Figma

## Project structure

```
pokok_id/
├── README.md
├── schema.sql      # database tables and dashboard queries
└── api.php         # API endpoints
```

## API endpoints

| Action | Description |
|--------|-------------|
| `all` | All trees for the map |
| `nearby` | Trees near a point, sorted by distance |
| `add` | Add a tree, with duplicate check within 3 m |
| `move` | Correct a tree's marker position |
| `passport` | Tree details, report history and adopter |
| `report` | File a problem report |
| `adopt` | Adopt a tree (one adopter per tree) |
| `street_density` | Streets ranked by trees per 100 m |

Example: `api.php?action=nearby&lat=3.0738&lng=101.5183&radius=100`

## Setup

1. Install XAMPP (or any PHP + MySQL stack).
2. Import `schema.sql` into MySQL. This creates the `pokok_id` database.
3. Edit the database credentials at the top of `api.php` if needed.
4. Place the project folder in `htdocs` and open it through `http://localhost/pokok_id/`.
5. For phone GPS testing, serve over HTTPS (for example with ngrok), since browsers require it for location access.

## Limitations

| Limitation | How we handle it |
|------------|------------------|
| GPS accuracy of about 1 to 5 m | Draggable marker correction and duplicate check; no survey-grade claims |
| Resident data may contain errors | Second-person verification and visible source labels |
| Low participation | Under-30-second tagging, adopt-a-tree badges, community events |
| Species and size are estimates | Labelled as estimates; manual entry allowed |
| Prototype uses pilot-area and sample data | Stated clearly in the pitch |
| No authentication yet | Prototype only; login planned for later phases |

## Roadmap

- **Prototype (hackathon):** tree map, passports, resident add and report flow, adoption, planner dashboard
- **Phase 2:** drone scans of a pilot township for automatic detection, plus building and shade analysis
- **Phase 3:** integration with the developer's township management and maintenance systems

## Pitch line

> "Plantations know every tree they own. Our townships don't. Pokok ID gives every tree an identity and every resident a reason to care."

## Acknowledgements

Built for the Sime Darby Property University Hackathon 2026. Map data from OpenStreetMap contributors.
