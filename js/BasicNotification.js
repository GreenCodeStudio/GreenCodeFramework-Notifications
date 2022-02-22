export class BasicNotification {
    constructor(message, link = null, stamp = null, expires = null) {
        if (stamp == null) stamp = new Date();
        if (expires == null) expires = new Date(+new Date() + 5000);
        this.message = message;
        this.link = link;
        this.stamp = stamp;
        this.expires = expires;
        this.changed=()=>{}
    }

}