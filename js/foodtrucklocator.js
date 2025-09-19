class FoodTruckLocator {
    locations = [];
    vacationMode = false;
    strings = {};
    markerColor = "#000";
    map;
    daysWithMarkers = [];
    oneOffDatesWithMarkers = [];
    layerGroupForDay;
    dayListContainer;

    constructor(
        locations,
        vacationMode,
        strings,
        markerColor,
        dayListContainer
    ) {
        this.locations = locations
            .map((loc) => ({
                ...loc,
                date:
                    loc.date !== "0000-00-00" && loc.date !== null
                        ? new Date(loc.date)
                        : null,
            }))
            .filter(
                (l) =>
                    l.date === null || l.date >= new Date().setHours(0, 0, 0, 0)
            );
        this.vacationMode = vacationMode;
        this.strings = strings;
        this.markerColor = markerColor;
        this.dayListContainer = dayListContainer;
        for (const [index, day] of strings.weekDays.entries()) {
            this.daysWithMarkers[index] = [];
        }
    }

    renderMap() {
        this.map = L.map("foodtrucklocator_map", {
            scrollWheelZoom: false,
        }).setView([44.9763, 5.108], 3);

        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution:
                '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(this.map);

        // Change the location array to group timetables by locations and add flag for current spot
        const locationsGrouped = [];
        for (const location of this.locations) {
            let existingLocationIndex = locationsGrouped.findIndex(
                (e) => e.location.id === parseInt(location.id)
            );
            let nowTimeTable = this.checkCurrentLocation(
                location.date,
                location.weekday,
                location.start_time,
                location.end_time
            );
            let timeTable = {
                id: parseInt(location.timetable_id),
                weekDay: parseInt(location.weekday),
                date: location.date,
                startTime: location.start_time,
                endTime: location.end_time,
                now: nowTimeTable,
                next: false,
            };
            if (existingLocationIndex > -1) {
                locationsGrouped[existingLocationIndex]["timeTables"].push(
                    timeTable
                );
                locationsGrouped[existingLocationIndex]["location"]["now"] =
                    locationsGrouped[existingLocationIndex]["location"][
                        "now"
                    ] || nowTimeTable;
            } else {
                locationsGrouped.push({
                    location: {
                        id: parseInt(location.id),
                        name: location.name,
                        description: location.description,
                        latitude: location.latitude,
                        longitude: location.longitude,
                        now: nowTimeTable,
                        next: false,
                    },
                    timeTables: [timeTable],
                });
            }
        }

        // Get the next closest timetable and add the flag
        const nextTimeTable = locationsGrouped
            .flatMap((l) =>
                l.timeTables.map((t) => ({
                    id: t.id,
                    date: this.getNextWeekDay(t.date, t.weekDay, t.startTime),
                }))
            )
            .sort((a, b) => a.date - b.date)[0];
        const locationToUpdate = locationsGrouped.find((l) =>
            l.timeTables.find((t) => t.id === nextTimeTable.id)
        );
        if (locationToUpdate && !locationToUpdate.location.now) {
            // Only show next spot when not current spot
            locationToUpdate.location.next = true;
            locationToUpdate.timeTables.find(
                (t) => t.id === nextTimeTable.id
            ).next = true;
        }

        // Add markers to the location object
        let currentLocationMarker = null;
        let nextLocationMarker = null;
        for (const locationGrouped of locationsGrouped) {
            const marker = L.marker(
                [
                    locationGrouped.location.latitude,
                    locationGrouped.location.longitude,
                ],
                {
                    icon: this.generateMarkerIcon(locationGrouped.location.now),
                }
            );
            marker.bindPopup(
                this.generateMarkerPopup(
                    locationGrouped.location,
                    locationGrouped.timeTables,
                    marker
                ),
                { autoClose: false }
            );
            locationGrouped.marker = marker;
            if (!this.vacationMode && locationGrouped.location.now) {
                // In vacation mode, no popup open
                currentLocationMarker = marker;
            }
            if (!this.vacationMode && locationGrouped.location.next) {
                nextLocationMarker = marker;
            }
        }

        const markers = locationsGrouped.map((l) => l.marker);
        if (markers.length > 0) {
            const group = L.featureGroup(markers).addTo(this.map);
            this.map.fitBounds(group.getBounds());
        }

        if (currentLocationMarker) {
            // Open only the current spot if any
            currentLocationMarker.openPopup();
        } else if (nextLocationMarker) {
            // Open the next spot
            nextLocationMarker.openPopup();
        }
    }

    generateMarkerPopup(location, timeTables, marker) {
        let content = `<div style="display: flex; align-items: center; margin-bottom: 0.75rem;">
            <div style="margin-right: 0.25rem;">📍</div>
            <div>
                <strong style="font-size: 1rem;">${location.name}</strong><br />
                ${location.description}
            </div>
            </div>`;
        if (timeTables.length > 0) {
            content +=
                '<div id="markerPopupContentTimeTables" style="display: flex; align-items: center;">';
            content +=
                '<div style="margin-right: 0.25rem;">📆</div><div><table style="border: none; margin: 0;">';
            const hasRegularSlots = timeTables.some((t) => t.date === null);
            if (hasRegularSlots) {
                content += `<tr><td colspan="2" style="border: none; padding: 0; color: ${this.markerColor}">${this.strings.regularSlots}</td></tr>`;
            }
            const oneOffTimeTables = [];
            for (const timeTable of timeTables) {
                if (timeTable.date) {
                    oneOffTimeTables.push(timeTable);
                    continue;
                }
                content += this.renderWeekdayInPopup(timeTable);

                // Create a list of markers associated to their day in week (for show days option)
                if (!this.daysWithMarkers[timeTable.weekDay].includes(marker)) {
                    this.daysWithMarkers[timeTable.weekDay].push(marker);
                }
            }
            oneOffTimeTables.sort((a, b) => a.date - b.date);
            if (oneOffTimeTables.length > 0) {
                content += `<tr><td colspan="2" style="border: none; padding: ${
                    hasRegularSlots ? "0.5rem" : "0"
                } 0 0 0; color: ${this.markerColor}">${
                    this.strings.oneoffDates
                }</td></tr>`;
                for (const oneOffTimeTable of oneOffTimeTables) {
                    content += this.renderWeekdayInPopup(oneOffTimeTable, true);
                    // Create a list of markers associated to their date (for show days option)
                    const existingDate = this.oneOffDatesWithMarkers.findIndex(
                        (d) =>
                            d.date.valueOf() === oneOffTimeTable.date.valueOf()
                    );
                    if (existingDate === -1) {
                        this.oneOffDatesWithMarkers.push({
                            date: oneOffTimeTable.date,
                            markers: [marker],
                        });
                    } else {
                        this.oneOffDatesWithMarkers[existingDate].markers.push(
                            marker
                        );
                    }
                }
                this.oneOffDatesWithMarkers.sort((a, b) => a.date - b.date);
            }
        }
        content += "</table></div></div>";
        return content;
    }

    renderWeekdayInPopup(timeTable, isOneOffDate = false) {
        return `<tr>
                    <td style="border: none; padding: 0;">
                        <strong>${
                            isOneOffDate
                                ? this.computeLocaleDate(timeTable.date)
                                : this.strings.weekDays[timeTable.weekDay]
                        }</strong>
                   </td>
                   <td style="border: none; padding: 0 0 0 0.25rem;">
                        ${this.computeLocaleTime(
                            timeTable.startTime
                        )} - ${this.computeLocaleTime(timeTable.endTime)}
                        ${
                            !this.vacationMode && timeTable.now
                                ? `<span class="timeTable now">${this.strings.now}</span>`
                                : ""
                        }
                        ${
                            !this.vacationMode && timeTable.next
                                ? `<span class="timeTable next">${this.strings.next}</span>`
                                : ""
                        }
                   </td> 
                </tr>`;
    }

    generateMarkerIcon(now) {
        return L.divIcon({
            className:
                "custom-marker" + (!this.vacationMode && now ? " now" : ""),
            iconAnchor: [15, 30],
            popupAnchor: [0, -30],
            html: `<div style="background-color: ${this.markerColor};"></div>`,
        });
    }

    checkCurrentLocation(date, day, startTime, endTime) {
        const now = new Date();
        const startTimeExploded = startTime.split(":");
        const endTimeExploded = endTime.split(":");
        const startTimeDate = date ? new Date(date.getTime()) : new Date();
        startTimeDate.setHours(startTimeExploded[0]);
        startTimeDate.setMinutes(startTimeExploded[1]);
        startTimeDate.setSeconds(0);
        startTimeDate.setMilliseconds(0);
        const endTimeDate = date ? new Date(date.getTime()) : new Date();
        endTimeDate.setHours(endTimeExploded[0]);
        endTimeDate.setMinutes(endTimeExploded[1]);
        endTimeDate.setSeconds(0);
        endTimeDate.setMilliseconds(0);
        return (
            now >= startTimeDate &&
            now < endTimeDate &&
            (date || now.getDay() === day)
        );
    }

    getNextWeekDay(date, day, time) {
        const now = new Date();
        const [hours, minutes] = time.split(":", 3);

        // Reference date with today time
        const todayAtTime = new Date(now.getTime());
        todayAtTime.setHours(hours, minutes, 0, 0);

        let nextDay;
        if (now.getDay() === day && now <= todayAtTime) {
            // Today is correct date and start time is not passed
            nextDay = todayAtTime;
        } else {
            // Find next compliant day if the week
            const daysToAdd = (day - now.getDay() + 7) % 7 || 7;
            nextDay = new Date(now.getTime());
            nextDay.setDate(now.getDate() + daysToAdd);
            nextDay.setHours(hours, minutes, 0, 0);
        }

        if (date) {
            // Check if the oneoff date is before the previous nextDay calculated
            const nextOneOffDate = new Date(date.getTime());
            nextOneOffDate.setHours(hours, minutes, 0, 0);
            if (nextOneOffDate >= now && nextOneOffDate < nextDay) {
                return nextOneOffDate;
            }
        }

        return nextDay;
    }

    computeLocaleTime(time) {
        const [hours, minutes] = time.split(":", 3);
        const timeObj = new Date();
        timeObj.setHours(hours);
        timeObj.setMinutes(minutes);
        timeObj.setSeconds(0);
        timeObj.setMilliseconds(0);
        let [timePart, meridianPart] = timeObj.toLocaleTimeString().split(" ");
        timePart = timePart.slice(0, -3); // Remove unseless seconds
        return timePart + (meridianPart ? " " + meridianPart : "");
    }

    computeLocaleDate(date) {
        return this.isToday(date)
            ? this.strings.today
            : date.toLocaleDateString();
    }

    generateDaysList(div) {
        if (this.daysWithMarkers.length > 0) {
            const p = document.createElement("p");
            p.innerHTML = this.strings.regularSlots;
            p.className = "header";
            p.style.backgroundColor = this.markerColor;
            div.appendChild(p);
        }
        for (const [day, markers] of this.daysWithMarkers.entries()) {
            if (markers.length > 0) {
                const p = this.renderDayInList(null, day, markers);
                div.appendChild(p);
            }
        }
        if (this.oneOffDatesWithMarkers.length > 0) {
            const p = document.createElement("p");
            p.innerHTML = this.strings.oneoffDates;
            p.className = "header";
            p.style.backgroundColor = this.markerColor;
            div.appendChild(p);
        }
        for (const oneOffDateMarker of this.oneOffDatesWithMarkers) {
            if (oneOffDateMarker.markers.length > 0) {
                const p = this.renderDayInList(
                    oneOffDateMarker.date,
                    null,
                    oneOffDateMarker.markers
                );
                div.appendChild(p);
            }
        }
    }

    renderDayInList(date, day, markers) {
        const p = document.createElement("p");
        p.className = "day";
        const isToday = date ? this.isToday(date) : this.isSameDayOfWeek(day);
        if (isToday) {
            p.style.backgroundColor = this.markerColor + "7a";
        }
        p.innerHTML =
            (date ? date.toLocaleDateString() : this.strings.weekDays[day]) +
            (markers.length > 1
                ? ` <span class="badgeCount">${markers.length}</span>`
                : ``);
        p.addEventListener(
            "mouseenter",
            () => (p.style.backgroundColor = this.markerColor)
        );
        p.addEventListener(
            "mouseleave",
            () =>
                (p.style.backgroundColor = isToday
                    ? this.markerColor + "7a"
                    : "transparent")
        );
        p.addEventListener("click", () =>
            this.openLocationsForDayOfWeek(date, day)
        );
        return p;
    }

    toggleDayList() {
        if (this.dayListContainer) {
            this.dayListContainer.classList.toggle("open");
        }
    }

    openLocationsForDayOfWeek(date, dayOfWeek) {
        const markers = date
            ? this.oneOffDatesWithMarkers.find(
                  (d) => d.date.valueOf() === date.valueOf()
              )?.markers || []
            : this.daysWithMarkers[dayOfWeek];
        if (markers.length > 0) {
            if (this.layerGroupForDay) {
                this.map.removeLayer(this.layerGroupForDay);
            }
            this.map.eachLayer((layer) => layer.closePopup());
            setTimeout(() => {
                for (const marker of markers) {
                    marker.openPopup();
                }
            }, 250);

            this.layerGroupForDay = L.featureGroup(markers).addTo(this.map);
            this.map.fitBounds(this.layerGroupForDay.getBounds(), {
                padding: [100, 100],
            });
            this.toggleDayList();
        }
    }

    isSameDayOfWeek(day) {
        return new Date().getDay() === day;
    }

    isToday(date) {
        const now = new Date();
        return (
            date.getFullYear() === now.getFullYear() &&
            date.getMonth() === now.getMonth() &&
            date.getDate() === now.getDate()
        );
    }
}
