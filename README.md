# Records in Contexts (RiC-CM) for ABCD v4

**RiC-CM** is an archival module developed for the ABCD (Automatización de Bibliotecas y Centros de Documentación) ecosystem, strictly based on the international ICA RiC-CM standard. This plugin offers a modern interface for managing archival entities, relationships, and controlled vocabularies, serving as an architectural model for the ABCD community’s transition to version 4.

## 🚀 Key Features

* **“Plug & Play” Architecture:** Isolated MVC routing in the `/content/plugins/ric_cm/` folder, ensuring that the system’s core remains untouched and secure.
* **Adaptive Installation (Auto-Discovery):** Native compilation of `.mst` and `.xrf` files during installation, automatically adapting to the server architecture (Windows/Linux) to eliminate the risk of database corruption.
* **WXIS Search and Pagination:** Advanced integration with ABCD’s native engine, enabling high-performance listings with asynchronous pagination.
* **Rigorous Internationalization (i18n):** Support for multiple languages via the system’s `LanguageManager`, with automatic fallback to English, eliminating hard-coded text.

## 📦 Installation

1. Download the latest version from the *Releases* section.
2. Extract the contents of the `.zip` file directly into the `/htdocs/content/plugins/ric_cm/` folder of your ABCD installation.
3. Log in to the system using your administrator credentials.
4. Navigate to the **Archives (RiC-CM)** module via the top navigation bar.
5. The build wizard will detect the absence of local binaries and initialize the `ric_cm` and `ric_msg` databases instantly.

## 🤝 Authorship and Credits

This module is the result of a collaborative effort dedicated to archival preservation and the modernization of free software:
* **Original Author:** Eustache Mêgnigbêto (Benin) – Responsible for the conceptualization, data modeling, and fundamental relational structure of the module in the ISIS format.
* **Modernization for ABCD v4:** Code refactored and encapsulated in the new MVC plugin standard by the ABCD Community.

## 📄 License

This project is an integral part of the ABCD ecosystem and is licensed under the global terms of the free software community.
