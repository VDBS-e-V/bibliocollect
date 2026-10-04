# BiblioCollect T1 v0.2.0

Inhalt des Patches:

- zentraler PermissionRegistry und kombinierbare Rollen-Bundles
- serverseitiges `permission`-Middleware und Laravel-Gates
- NavigationRegistry für Public, Portal, POS und Administration
- VDBS App-Shell mit Skip-Link, Navigation, Light/Dark-Umschalter und Surface-Kennzeichnung
- UI-Basiskomponenten für Button, Card, Input, Select, Alert, Badge, Page Header und Tabelle
- lokale Entwicklungsansichten für Portal, POS und Administration
- fachliche Zeitzone über `BusinessClock` mit Standard `Europe/Berlin`
- Entkopplung `Identity -> Patrons` auf Modulebene
- Architektur- und Feature-Tests für die T1-Infrastruktur
- Dokumentation unter `docs/T1_APPLICATION_INFRASTRUCTURE.md`

Die Persistenz von Onlinekonten, Rollen- und Permission-Zuweisungen folgt erst in T2.
