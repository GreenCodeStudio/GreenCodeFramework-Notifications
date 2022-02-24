import {Ajax} from "../../Core/js/ajax";

export const NotificationManager = {
    current: [],
    onChangeListeners: [],
    show(notification) {
        console.log({notification})
        if (notification.expires) {
            if (notification.expires < new Date()) {
                return;
            } else {
                setTimeout(() => this.checkExpired(), notification.expires - new Date());
            }
        }
        this.current.push(notification);
        notification.changed = () => {
            this.changed();
            if (notification.expires) {
                if (notification.expires < new Date()) {
                    this.checkExpired();
                } else {
                    setTimeout(() => this.checkExpired(), notification.expires - new Date());
                }
            }
        };
        this.changed();
    },
    remove(notification) {
        const index = this.current.indexOf(notification);
        if (index >= 0) {
            this.current.splice(index, 1);
        }
        this.changed();
        if (notification.id) {
            Ajax.Notifications.hide(notification.id);
        }
    },
    changed() {
        for (const onChangeListener of this.onChangeListeners) {
            try {
                onChangeListener();
            } catch (ex) {
            }
        }
    },
    checkExpired() {
        let changed = false;
        for (let i = 0; i < this.current.length; i++) {
            if (this.current[i].expires && this.current[i].expires <= new Date()) {
                this.current.splice(i, 1);
                i--;
                changed = true;
            }
        }
        if (changed) {
            this.changed();
        }
    },
    onchange(callback) {
        this.onChangeListeners.push(callback)
    }
}
if (window.initNotifications) {
    for (const notification of window.initNotifications) {
        notification.expires = notification.expiresRelative == null ? null : new Date(+new Date() + notification.expiresRelative * 1000)

        NotificationManager.show(notification)
    }
}