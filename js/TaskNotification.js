import {BasicNotification} from "./BasicNotification";
import {NotificationsRenderer} from "../../CommonBase/js/NotificationsRenderer";
import {NotificationManager} from "./NotificationsManager";

export class TaskNotification extends BasicNotification {
    constructor(pendingMessage, successMessage) {
        super(pendingMessage);
        this.pendingMessage = pendingMessage;
        this.successMessage = successMessage;
    }

    async run(fun) {
        try {
            let ret = await fun();
            this.message = this.successMessage;
            this.expires = new Date(+new Date() + 5000);
            this.changed();
            return ret
        } catch (ex) {
            this.message = "Wystąpił błąd";
            this.changed();
            throw ex;
        }
    }

    static Create(fun, pendingMessage, successMessage) {
        const notification = new TaskNotification(pendingMessage, successMessage)
        NotificationManager.show(notification);
        return notification.run(fun);
    }
}